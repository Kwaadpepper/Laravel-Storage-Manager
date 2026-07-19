import { useEffect, useRef, useState } from "react";


interface ViewAudioProps {
  readonly blob: Blob;
  readonly fileName: string;
}

export default function ViewAudio({ blob, fileName }: Readonly<ViewAudioProps>) {
  const [objectUrl, setObjectUrl] = useState<string | null>(null);
  const prevUrlRef = useRef<string | null>(null);

  useEffect(() => {
    const url = URL.createObjectURL(blob);
    setTimeout(() => setObjectUrl(url), 0);
    prevUrlRef.current = url;

    return () => {
      URL.revokeObjectURL(url);
    };
  }, [blob]);

  if (!objectUrl) return null;

  return (
    <div className="flex flex-col items-center justify-center h-full gap-4">
      <p className="text-base-content/60 text-sm">{fileName}</p>
      <audio controls src={objectUrl} className="w-full max-w-md" />
    </div>
  );
}
