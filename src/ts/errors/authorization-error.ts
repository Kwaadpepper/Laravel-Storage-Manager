export class AuthorizationError extends Error {

  constructor(
    private readonly reason: string = '',
    message?: string,
    options?: ErrorOptions
  ) {
    super(message, options)
  }

  public getReason(): string {
    return this.reason
  }
}
