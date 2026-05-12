export abstract class Str {
  // * CONSTANTS
  static readonly UNITS = [
    'byte',
    'kilobyte',
    'megabyte',
    'gigabyte',
    'terabyte',
    'petabyte',
  ]

  static readonly BYTES_PER_KB = 1024

  public static ucFirst(value: string): string {
    return value.at(0)?.toUpperCase() + value.slice(1)
  }

  public static toCamelCase(value: string): string {
    return value.replaceAll(/(?:^\p{L}*|[A-Z]|\b\p{L}*)/gu, function (word, index) {
      return index === 0 ? word.toLowerCase() : word.toUpperCase()
    }).replaceAll(/\s+/g, '')
  }

  public static toSnakeCase(value: string): string {
    return value.replaceAll(/\W+/g, ' ')
      .split(/ |\B(?=[A-Z])/)
      .map(word => word.toLowerCase())
      .join('_').replace(/^(_+)?(.*)/, '$2').replace(/_*$/, '')
  }

  public static toTitleCase(value: string): string {
    return value.replaceAll(
      /\p{L}*\S*/gu,
      text => text.charAt(0).toUpperCase() + text.substring(1).toLowerCase(),
    )
  }

  public static toKebabCase(value: string): string {
    return value.replaceAll(/([a-z])([A-Z])/g, '$1-$2')
      .replaceAll(/[\s_]+/g, '-')
      .toLowerCase()
  }

  public static limit(value: string, limit = 25, ellipsis = '...'): string {
    if (limit < 1) {
      throw new Error('Limit cannot be less than 1')
    }
    return value.slice(0, limit) + (value.length > limit ? ellipsis : '')
  }

  /** Human file size change. */
  public static humanFileSize(sizeBytes: number): string {
    let size = Math.abs(Number(sizeBytes))

    let u = 0
    while (size >= this.BYTES_PER_KB && u < this.UNITS.length - 1) {
      size /= this.BYTES_PER_KB
      ++u
    }

    return new Intl.NumberFormat([], {
      style: 'unit',
      unit: this.UNITS[u],
      unitDisplay: 'short',
      maximumFractionDigits: 1,
    }).format(size)
  }
}
