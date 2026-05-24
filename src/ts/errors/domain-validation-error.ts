export function isDomainValidationError(e: unknown): e is DomainValidationError {
  return e instanceof DomainValidationError
}

export class DomainValidationError extends Error {
  constructor(
    private readonly domainCode: number,
    message?: string,
  ) {
    super(message)
  }

  public getDomainCode(): number {
    return this.domainCode
  }
}
