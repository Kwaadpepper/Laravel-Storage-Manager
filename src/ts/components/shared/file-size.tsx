interface FileSizeProps {
    readonly bytes: number
}

export default function FileSize({ bytes }: Readonly<FileSizeProps>) {
    const formatted = formatBytes(bytes)

    return <span>{formatted}</span>
}

function formatBytes(bytes: number): string {
    if (bytes === 0) return '0 o'

    const units = ['o', 'Ko', 'Mo', 'Go', 'To']
    const i = Math.min(Math.floor(Math.log2(bytes) / 10), units.length - 1)

    if (i === 0) return `${bytes} o`

    const value = bytes / Math.pow(1024, i)
    return `${value.toFixed(2).replace(/\.?0+$/, '')} ${units[i]}`
}
