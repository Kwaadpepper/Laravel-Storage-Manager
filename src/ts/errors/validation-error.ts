import { ServerFieldsErrors } from './../types/server-field-errors.type';

export function isValidationError<TFieldName extends string = string>(e: unknown): e is ValidationError<TFieldName> {
  return e instanceof ValidationError
}

export class ValidationError<TFieldName extends string = string> extends Error {

  constructor(
    private readonly fieldsErrors: ServerFieldsErrors<TFieldName>,
    message?: string
  ) {
    super(message)
  }

  public getFieldErrors(): ServerFieldsErrors<TFieldName> {
    return this.fieldsErrors
  }
}
