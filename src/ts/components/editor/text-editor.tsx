import { Editor } from 'prism-react-editor';
import { useEffect, useMemo, useState } from 'react';

import copyBtnCss from "prism-react-editor/copy-button.css?inline";
import layoutCss from 'prism-react-editor/layout.css?inline';
import searchCss from 'prism-react-editor/search.css?inline';
import darkThemeCss from 'prism-react-editor/themes/vs-code-dark.css?inline';
import lightThemeCss from 'prism-react-editor/themes/vs-code-light.css?inline';

import 'prism-react-editor/languages';

import 'prism-react-editor/prism/languages';

import { useContainer } from '@ts/container';
import { Theme } from '@ts/stores';
import { useUiStore } from '@ts/stores';
import { useDefaultCommands, useEditHistory } from 'prism-react-editor/commands';
import { useCopyButton } from 'prism-react-editor/copy-button';
import { useCursorPosition } from 'prism-react-editor/cursor';
import { usePrismEditor } from 'prism-react-editor/extensions';
import { IndentGuides } from "prism-react-editor/guides";
import { useHighlightBracketPairs } from 'prism-react-editor/highlight-brackets';
import { useBracketMatcher } from 'prism-react-editor/match-brackets';
import { useHighlightMatchingTags, useTagMatcher } from 'prism-react-editor/match-tags';
import { useOverscroll } from 'prism-react-editor/overscroll';
import { useHighlightSelectionMatches, useSearchWidget, useShowInvisibles } from 'prism-react-editor/search';

import { SUPPORTED_LANGUAGES } from "../../utils/supported-languages";


interface TextEditorProps {
  value?: string;
  language?: keyof typeof SUPPORTED_LANGUAGES;
  readOnly?: boolean;
  onChange?: (value: string) => void;
  onLanguageChange?: (language: keyof typeof SUPPORTED_LANGUAGES) => void;
}

function MyExtensions() {
  const [editor] = usePrismEditor()
  useBracketMatcher(editor)
  useHighlightBracketPairs(editor)
  useOverscroll(editor)
  useTagMatcher(editor)
  useHighlightMatchingTags(editor)
  useDefaultCommands(editor)
  useEditHistory(editor)
  useSearchWidget(editor)
  useHighlightSelectionMatches(editor)
  useShowInvisibles(editor)
  useCopyButton(editor)
  useCursorPosition(editor)

  return <IndentGuides />
}

export default function TextEditor({
  value = '',
  language = 'XML doc (.net)',
  readOnly = false,
  onChange,
  onLanguageChange,
}: Readonly<TextEditorProps>) {
  const { theme } = useUiStore()
  const container = useContainer()
  const themeService = container.resolve('themeService')

  const [currentLanguage, setCurrentLanguage] = useState(language);
  const [editorTheme, setEditorTheme] = useState<Theme>(themeService.getSystemTheme());
  const prismLang = useMemo(() => {
    const langs = SUPPORTED_LANGUAGES[currentLanguage];
    return langs ? langs[0] : 'text';
  }, [currentLanguage]);

  useEffect(() => {
    if (theme !== 'auto') {
      setTimeout(() => setEditorTheme(theme), 0);
      return;
    }

    setTimeout(() => setEditorTheme(themeService.getSystemTheme()), 0);

    return themeService.listenToSystemThemeChanges((prefersDark) => {
      setEditorTheme(prefersDark ? 'dark' : 'light');
    });
  }, [theme, themeService]);

  function handleLanguageChange(e: React.ChangeEvent<HTMLSelectElement>) {
    const lang = e.target.value as keyof typeof SUPPORTED_LANGUAGES;
    setCurrentLanguage(lang);
    onLanguageChange?.(lang);
  }

  return (
    <div className="flex flex-col h-full gap-1">
      <div className="flex items-center gap-2">
        <label htmlFor="text-editor-language" className="text-xs text-base-content/60 shrink-0">Language</label>
        <select
          id="text-editor-language"
          className="select select-xs select-bordered"
          value={currentLanguage}
          onChange={handleLanguageChange}
        >
          {Object.keys(SUPPORTED_LANGUAGES).map((lang) => (
            <option key={lang} value={lang}>{lang}</option>
          ))}
        </select>
      </div>

      <div className="flex-1 min-h-0">
        <style>{layoutCss}</style>
        <style>{searchCss}</style>
        <style>{copyBtnCss}</style>
        {editorTheme === 'light' ? <style>{lightThemeCss}</style> : <style>NO LIGHT</style>}
        {editorTheme === 'dark' ? <style>{darkThemeCss}</style> : <style>NO DARK</style>}
        <Editor
          language={prismLang}
          value={value}
          readOnly={readOnly}
          wordWrap
          lineNumbers
          insertSpaces
          style={{ height: '100%' }}
          onUpdate={onChange}
        >
          <MyExtensions />
        </Editor>
      </div>
    </div>
  );
}
