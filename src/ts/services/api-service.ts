
import { HttpClient, RequestParameters } from '@ts/clients';
import { AuthorizationError, NotFoundError, ServerError, UnexpectedError, ValidationError } from '@ts/errors';
import { apiResponseSchema, authorizationErrorSchema, validationErrors } from '@ts/schemas';
import { Str } from '@ts/tools';
import { JsonValue } from '@ts/types';
import z from 'zod/v4';

export class ApiService {

  constructor(private readonly httpClient: HttpClient) {
    this.httpClient = httpClient
  }


  async get<T extends z.ZodTypeAny>(path: string, schema: T): Promise<z.output<T>>;
  async get(path: string, schema?: null): Promise<void>;
  async get<T extends z.ZodTypeAny>(path: string, schema?: T | null): Promise<z.output<T> | void> {
    const response = await this.httpClient.get(path)
    return schema ? this.reponseToData(response, schema) : undefined
  }

  async post(path: string, data: RequestParameters): Promise<void>;
  async post<T extends z.ZodTypeAny>(path: string, data: RequestParameters, schema: T): Promise<z.output<T>>;
  async post<T extends z.ZodTypeAny>(path: string, data: RequestParameters, schema?: T): Promise<z.output<T> | void> {
    const response = await this.httpClient.post(path, data)
    return schema ? this.reponseToData(response, schema) : undefined
  }

  async put(path: string, data: RequestParameters): Promise<void>;
  async put<T extends z.ZodTypeAny>(path: string, data: RequestParameters, schema: T): Promise<z.output<T>>;
  async put<T extends z.ZodTypeAny>(path: string, data: RequestParameters, schema?: T): Promise<z.output<T> | void> {
    const response = await this.httpClient.put(path, data)
    return schema ? this.reponseToData(response, schema) : undefined
  }

  async patch(path: string, data: RequestParameters): Promise<void>;
  async patch<T extends z.ZodTypeAny>(path: string, data: RequestParameters, schema: T): Promise<z.output<T>>;
  async patch<T extends z.ZodTypeAny>(path: string, data: RequestParameters, schema?: T): Promise<z.output<T> | void> {
    const response = await this.httpClient.patch(path, data)
    return schema ? this.reponseToData(response, schema) : undefined
  }

  async delete(path: string, data?: RequestParameters): Promise<void> {
    await this.httpClient.delete(path, data)
  }

  private async reponseToData<T extends z.ZodTypeAny>(response: Response, schema: T): Promise<z.output<T>> {
    const dataResponse = await this.reponseToJson(response)
    const apiResponse = apiResponseSchema.parse(dataResponse)
    const result = schema.safeParse(apiResponse.data)

    if (response.status === 403) {
      const message = 'Not authorized, CODE ' + response.status
      const authorizationError = authorizationErrorSchema.safeParse(dataResponse)
      throw new AuthorizationError(authorizationError.data?.reason ?? 'Unauthorized', message)
    }

    if (response.status === 404) {
      const message = 'Not found, CODE ' + response.status

      throw new NotFoundError(message)
    }

    if (response.status === 422) {
      const message = 'Invalid request, CODE ' + response.status

      if (schema instanceof z.ZodObject) {


        // Try to map response error field to our schema
        const propertyNames = Object.keys(schema.shape)
        const jsonErrors = validationErrors.safeParse(dataResponse)
        const errors: Record<string, string> = jsonErrors.data?.errors ?? {}
        const mappedErrors: Record<string, string> = {}

        for (const [key, value] of Object.entries(errors)) {
          const matchingPropertyName = propertyNames.find(property => property === key)
            ?? propertyNames.find(property => property === Str.toCamelCase(key))
            ?? propertyNames.find(property => property === Str.toSnakeCase(key))
            ?? Str.toKebabCase(key)
          mappedErrors[matchingPropertyName] = value
        }
        throw new ValidationError(mappedErrors ?? {}, message)
      }

      throw new ValidationError(dataResponse?.['errors' as keyof typeof dataResponse] ?? {}, message)
    }

    if (!result.success) {
      const message = 'Failed to validate server response, CODE ' + response.status

      console.debug('Validation errors', result.error)

      throw new ServerError(message)
    }

    return result.data
  }

  private async reponseToJson(response: Response): Promise<JsonValue> {
    try {
      const body = await response.text()
      return JSON.parse(body)
    }
    catch (cause) {
      const message = 'Failed to decode reponse as json' + (cause instanceof Error ? `: ${cause.message}` : '')

      throw new UnexpectedError(message)
    }
  }
}
