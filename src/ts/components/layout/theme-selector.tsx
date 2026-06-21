import { useContainer } from "@ts/container";
import { useUiStore } from "@ts/stores";
import { LucideMoon, LucideSun, LucideSunMoon } from "lucide-react";
import { useEffect, useRef } from "react";

interface ThemeSelectorProps {
}

export default function ThemeSelector(_: Readonly<ThemeSelectorProps>) {
  const { theme } = useUiStore()
  const container = useContainer()
  const themeService = container.resolve('themeService')

  const themeChangeInputRef = useRef<HTMLInputElement>(null)

  useEffect(() => {
    if (themeChangeInputRef.current) {
      themeChangeInputRef.current.indeterminate = theme === 'auto';
    }
  }, [theme])

  function rotateTheme() {
    switch (theme) {
      case 'auto':
        themeService.setTheme('light')
        break
      case 'light':
        themeService.setTheme('dark')
        break
      case 'dark':
        themeService.setTheme('auto')
        break
    }
  }

  function onKeyDownThemeSwapInput(e: React.KeyboardEvent<HTMLInputElement>): void {
    if (e.key === 'Enter') {
      rotateTheme()
    }
  }

  function onThemeChange(): void {
    rotateTheme()
  }

  return (
    <div className="theme-selector">
      <button
        className="btn btn-ghost btn-sm swap swap-rotate"
        title="Toggle theme"
        onClick={rotateTheme}
      >
        <input
          className="hidden w-0 h-0"
          ref={themeChangeInputRef}
          type="checkbox"
          checked={theme === 'light'}
          onChange={onThemeChange}
          onKeyDown={onKeyDownThemeSwapInput}
          aria-label="Toggle theme"
        />
        <span className="flex justify-center align-middle items-center swap-indeterminate">
          <LucideSunMoon />
        </span>
        <span className="flex justify-center align-middle items-center swap-off">
          <LucideMoon />
        </span>
        <span className="flex justify-center align-middle items-center swap-on">
          <LucideSun />
        </span>
      </button>
    </div>
  )
}
