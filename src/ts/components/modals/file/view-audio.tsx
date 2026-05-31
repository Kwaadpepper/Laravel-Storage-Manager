import { useEffect, useRef, useState } from "react";

export const SUPPORTED_EXTENSIONS: readonly string[] = [
  'mp3', 'wav', 'ogg', 'flac', 'm4a', 'aac', 'opus', 'weba',
];

interface ViewAudioProps {
  readonly blob: Blob;
  readonly fileName: string;
}

export default function ViewAudio({ blob, fileName }: Readonly<ViewAudioProps>) {
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
    <div className="flex flex-col items-center justify-center h-full gap-4">
      <p className="text-base-content/60 text-sm">{fileName}</p>
      {/* eslint-disable-next-line jsx-a11y/media-has-caption */}
      <audio controls src={objectUrl} className="w-full max-w-md" />
    </div>
  );
}
