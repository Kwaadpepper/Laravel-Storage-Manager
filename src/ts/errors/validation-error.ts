import { ServerFieldsErrors } from './../types/server-field-errors.type';


export class ValidationError extends Error {

  constructor(
    private readonly fieldsErrors: ServerFieldsErrors,
    message?: string
  ) {
    super(message)
  }

  public getFieldErrors(): ServerFieldsErrors {
    return this.fieldsErrors
  }
}
