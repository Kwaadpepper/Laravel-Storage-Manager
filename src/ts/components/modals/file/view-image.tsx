import { useEffect, useRef, useState } from "react";

export const SUPPORTED_EXTENSIONS: readonly string[] = [
  'png', 'jpg', 'jpeg', 'gif', 'webp', 'svg', 'bmp', 'ico', 'avif', 'tiff', 'tif',
];

interface ViewImageProps {
  readonly blob: Blob;
  readonly fileName: string;
}

export default function ViewImage({ blob, fileName }: Readonly<ViewImageProps>) {
  const [objectUrl, setObjectUrl] = useState<string | null>(null);
  const prevUrlRef = useRef<string | null>(null);

  useEffect(() => {
    const url = URL.createObjectURL(blob);
    setObjectUrl(url);
    prevUrlRef.current = url;

    return () => {
      URL.revokeObjectURL(url);
    };
  }, [blob]);

  if (!objectUrl) return null;

  return (
    <div className="flex items-center justify-center h-full overflow-auto">
      <img
        src={objectUrl}
        alt={fileName}
        className="max-w-full max-h-full object-contain"
      />
    </div>
  );
}
