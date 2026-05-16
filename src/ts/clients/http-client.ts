import { ServerError } from "@ts/errors";

export enum HttpMethod {
  GET = 'GET',
  POST = 'POST',
  PUT = 'PUT',
  PATCH = 'PATCH',
  DELETE = 'DELETE',
};

export type RequestParameters = Record<string, string | string[] | boolean | number | null | undefined>
export type MultiPartRequestParameters = FormData
export type CallResponse = Promise<Response>

export class HttpClient {
  readonly #defautlHeaders: HeadersInit = {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    'Accept-Language': 'fr',
  }

  readonly #endpointHost: URL

  constructor(endpointHost: URL) {
    if (endpointHost.host.length === 0) {
      throw new Error('Host is required')
    }
    this.#endpointHost = endpointHost
  }

  /**
   * Get an url to make calls
   * @param path Url path append
   * @returns a new URL with to make calls within HTTP client
   */
  public pathUrl(path: string): URL {
    return new URL(path, this.#endpointHost)
  }

  public sendMultiPartFile(path: string, args: MultiPartRequestParameters, headers: HeadersInit = {}): CallResponse {
    const url = this.pathUrl(path)
    const init: RequestInit = {
      method: HttpMethod.POST,
    }, initHeaders = this.setupHeaders(headers)

    delete initHeaders['Content-Type' as keyof typeof initHeaders]

    url.host = this.#endpointHost.host
    url.protocol = this.#endpointHost.protocol

    init.headers = initHeaders
    init.body = args

    return this.fetch(url, init)
  }

  public stream(path: string, args: RequestParameters = {}, headers: HeadersInit = {}): CallResponse {
    return this.call(HttpMethod.GET, path, args, headers)
  }

  public get(path: string, args: RequestParameters = {}, headers: HeadersInit = {}): CallResponse {
    return this.call(HttpMethod.GET, path, args, headers)
  }

  public post(path: string, args: RequestParameters = {}, headers: HeadersInit = {}): CallResponse {
    return this.call(HttpMethod.POST, path, args, headers)
  }

  public patch(path: string, args: RequestParameters = {}, headers: HeadersInit = {}): CallResponse {
    return this.call(HttpMethod.PATCH, path, args, headers)
  }

  public put(path: string, args: RequestParameters = {}, headers: HeadersInit = {}): CallResponse {
    return this.call(HttpMethod.PUT, path, args, headers)
  }

  public delete(path: string, args: RequestParameters = {}, headers: HeadersInit = {}): CallResponse {
    return this.call(HttpMethod.DELETE, path, args, headers)
  }

  public call(method: HttpMethod, path: string, args: RequestParameters = {}, headers: HeadersInit = {}): CallResponse {
    const url = this.pathUrl(path)
    const init = this.makeCallInit(method, url, args, headers)
    return this.fetch(url, init)
  }

  private makeCallInit(method: HttpMethod, url: URL, args: RequestParameters = {}, headers: HeadersInit = {}): RequestInit {
    const init: RequestInit = {
      method: method.toString(),
    }

    init.headers = this.setupHeaders(headers)

    url.host = this.#endpointHost.host
    url.protocol = this.#endpointHost.protocol

    this.setupParameters(init, method, url, args)

    return init
  }

  private setupHeaders(headers: HeadersInit = {}): HeadersInit {
    const httpHeaders: HeadersInit = { ...this.#defautlHeaders, ...headers }

    return httpHeaders
  }

  private setupParameters(init: RequestInit, method: HttpMethod, url: URL, args: RequestParameters = {}): void {
    if (method === HttpMethod.GET) {
      const searchParams = new URLSearchParams(url.search);
      Object.keys(args).forEach(key => searchParams.set(key, String(args[key])))
      url.search = searchParams.toString()
    }
    else {
      init.body = JSON.stringify(args)
    }
  }

  /**
   * Make a fetch call
   * @throws ServerError In case of a communication error
   */
  private fetch(url: URL, init: RequestInit): CallResponse {
    return fetch(url, init).then(this.checkReponseIs200)
      .catch(this.handleResponseIs500).then()
  }

  private checkReponseIs200(response: Response): Response {
    // ?  Between 200 and 299 status, ignore 4xx ?
    if (!response.ok && !(response.status >= 400 && response.status < 499)) {
      const message = `Server Response was not ok '${response.status}'`
      throw new ServerError(message)
    }
    return response
  }

  private handleResponseIs500(cause: unknown): void {
    if (cause instanceof Error) {
      const message = `Call failed '${cause.message}'`
      throw new ServerError(message)
    }

    throw new ServerError('Unknown error when calling server')
  }
}
