export type Path = string & {
  type: 'Path'
}

export function rootPath(): Path {
  return '/' as Path
}
