import { SUPPORTED_LANGUAGES } from "@ts/utils/supported-languages";
import { lazy, Suspense, useEffect, useState } from "react";

const TextEditor = lazy(() => import("@ts/components/editor/text-editor"));

function detectLanguage(extension: string | null): keyof typeof SUPPORTED_LANGUAGES {
  if (!extension) return 'Text';
  const ext = extension.toLowerCase();
  const keys = Object.keys(SUPPORTED_LANGUAGES) as (keyof typeof SUPPORTED_LANGUAGES)[];
  return keys.find(label => (SUPPORTED_LANGUAGES[label] as readonly string[]).includes(ext)) ?? 'Text';
}

interface ViewTextProps {
  readonly blob: Blob;
  readonly extension: string | null;
}

export default function ViewText({ blob, extension }: Readonly<ViewTextProps>) {
  const [content, setContent] = useState('');

  useEffect(() => {
    blob.text()
      .then(text => setContent(text))
      .catch(() => setContent(''));
  }, [blob]);

  const language = detectLanguage(extension);

  return (
    <div className="h-full overflow-hidden">
      <Suspense fallback={<div className="flex items-center justify-center h-full text-base-content/50">Loading…</div>}>
        <TextEditor
          value={content}
          language={language}
          readOnly
        />
      </Suspense>
    </div>
  );
}
