
import { HttpClient, RequestParameters } from '@ts/clients';
import { AuthorizationError, DomainValidationError, NotFoundError, ServerError, UnexpectedError, ValidationError } from '@ts/errors';
import { apiResponseSchema, authorizationErrorSchema, domainValidationErrorSchema, validationErrors } from '@ts/schemas';
import { Str } from '@ts/tools';
import { JsonValue } from '@ts/types';
import * as z from 'zod/v4';

export class ApiService {

  constructor(private readonly httpClient: HttpClient) {}


  async get<T extends z.ZodTypeAny>(path: string, schema: T): Promise<z.output<T>>;
  async get(path: string, schema?: null): Promise<void>;
  async get<T extends z.ZodTypeAny>(path: string, schema?: T | null): Promise<z.output<T> | void> {
    const response = await this.httpClient.get(path)
    if (schema) {
      return this.responseToData(response, schema)
    }

    await this.throwForErrorStatus(response)
    return undefined
  }

  async post(path: string, data: RequestParameters): Promise<void>;
  async post<T extends z.ZodTypeAny>(path: string, data: RequestParameters, schema: T): Promise<z.output<T>>;
  async post<T extends z.ZodTypeAny>(path: string, data: RequestParameters, schema?: T): Promise<z.output<T> | void> {
    const response = await this.httpClient.post(path, data)
    if (schema) {
      return this.responseToData(response, schema)
    }

    await this.throwForErrorStatus(response)
    return undefined
  }

  async postMultiPart(path: string, data: FormData): Promise<void>;
  async postMultiPart<T extends z.ZodTypeAny>(path: string, data: FormData, schema: T): Promise<z.output<T>>;
  async postMultiPart<T extends z.ZodTypeAny>(path: string, data: FormData, schema?: T): Promise<z.output<T> | void> {
    const response = await this.httpClient.sendMultiPartFile(path, data)
    if (schema) {
      return this.responseToData(response, schema)
    }

    await this.throwForErrorStatus(response)
    return undefined
  }

  async put(path: string, data: RequestParameters): Promise<void>;
  async put<T extends z.ZodTypeAny>(path: string, data: RequestParameters, schema: T): Promise<z.output<T>>;
  async put<T extends z.ZodTypeAny>(path: string, data: RequestParameters, schema?: T): Promise<z.output<T> | void> {
    const response = await this.httpClient.put(path, data)
    if (schema) {
      return this.responseToData(response, schema)
    }

    await this.throwForErrorStatus(response)
    return undefined
  }

  async patch(path: string, data: RequestParameters): Promise<void>;
  async patch<T extends z.ZodTypeAny>(path: string, data: RequestParameters, schema: T): Promise<z.output<T>>;
  async patch<T extends z.ZodTypeAny>(path: string, data: RequestParameters, schema?: T): Promise<z.output<T> | void> {
    const response = await this.httpClient.patch(path, data)
    if (schema) {
      return this.responseToData(response, schema)
    }

    await this.throwForErrorStatus(response)
    return undefined
  }

  async delete(path: string, data?: RequestParameters): Promise<void> {
    await this.httpClient.delete(path, data)
  }

  private async throwForErrorStatus<T extends z.ZodTypeAny>(response: Response, schema?: T): Promise<void> {
    if (response.ok) {
      return
    }

    const dataResponse = await this.responseToJson(response)

    if (response.status === 403) {
      const message = 'Not authorized, CODE ' + response.status
      const authorizationError = authorizationErrorSchema.safeParse(dataResponse)
      throw new AuthorizationError(authorizationError.data?.reason ?? 'Unauthorized', message)
    }

    if (response.status === 404) {
      const message = 'Not found, CODE ' + response.status
      throw new NotFoundError(message)
    }

    if (response.status === 400 || response.status === 422) {
      if (response.status === 422) {
        const domainError = domainValidationErrorSchema.safeParse(dataResponse);
        if (domainError.success) {
          throw new DomainValidationError(
            domainError.data.errors.code,
            domainError.data.errors.message,
          );
        }
      }

      const jsonErrors = validationErrors.safeParse(dataResponse);
      if (jsonErrors.success) {
        const message = (response.status === 400 ? 'Invalid request' : 'Validation failed') + ', CODE ' + response.status;
        const errors: Record<string, string> = jsonErrors.data.errors ?? {};

        if (schema instanceof z.ZodObject) {
          const propertyNames = Object.keys(schema.shape);
          const mappedErrors: Record<string, string> = {};

          for (const [key, value] of Object.entries(errors)) {
            const matchingPropertyName = propertyNames.find(property => property === key)
              ?? propertyNames.find(property => property === Str.toCamelCase(key))
              ?? propertyNames.find(property => property === Str.toSnakeCase(key))
              ?? Str.toKebabCase(key);
            mappedErrors[matchingPropertyName] = value;
          }

          throw new ValidationError(mappedErrors, message);
        }

        throw new ValidationError(errors, message);
      }

      if (response.status === 422) {
        const message = 'Business rule failed, CODE ' + response.status;
        throw new DomainValidationError(0, message);
      }

      throw new ServerError('Request failed, CODE ' + response.status);
    }

    throw new ServerError('Request failed, CODE ' + response.status)
  }

  private async responseToData<T extends z.ZodTypeAny>(response: Response, schema: T): Promise<z.output<T>> {
    await this.throwForErrorStatus(response, schema)

    const dataResponse = await this.responseToJson(response)
    const apiResponse = apiResponseSchema.parse(dataResponse)
    const result = schema.safeParse(apiResponse.data)

    if (!result.success) {
      const message = 'Failed to validate server response, CODE ' + response.status

      throw new ServerError(message)
    }

    return result.data
  }

  private async responseToJson(response: Response): Promise<JsonValue> {
    try {
      const body = await response.text()
      return JSON.parse(body)
    }
    catch (cause) {
      const message = 'Failed to decode response as json' + (cause instanceof Error ? `: ${cause.message}` : '')

      throw new UnexpectedError(message)
    }
  }
}
