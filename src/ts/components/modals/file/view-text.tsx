import { SUPPORTED_LANGUAGES } from "@ts/components/editor/text-editor";
import { lazy, Suspense, useEffect, useState } from "react";

const TextEditor = lazy(() => import("@ts/components/editor/text-editor"));

function detectLanguage(extension: string | null): keyof typeof SUPPORTED_LANGUAGES {
  if (!extension) return 'Text';
  const ext = extension.toLowerCase();
  for (const [label, aliases] of Object.entries(SUPPORTED_LANGUAGES)) {
    if ((aliases as readonly string[]).includes(ext)) {
      return label;
    }
  }
  return 'Text';
}

export const SUPPORTED_EXTENSIONS: readonly string[] = [
  ...new Set(Object.values(SUPPORTED_LANGUAGES).flat()),
  'txt', 'text', 'log', 'env',
];

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
