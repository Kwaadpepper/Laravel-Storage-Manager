import { useContainer } from "@ts/container";
import { useEffect } from "react";

export function useKeyboardNavigation() {
  const container = useContainer()
  const navigationService = container.resolve('navigationService')

  useEffect(() => {
    function onDocKeyDown(e: React.KeyboardEvent<HTMLElement>) {
      const target = e.target as HTMLElement | null
      if (target?.tagName === 'INPUT' || target?.tagName === 'TEXTAREA' || target?.isContentEditable) {
        return
      }
      if (e.key === 'Backspace') {
        e.preventDefault()
        navigationService.navigatePrevious()
      }
    }
    document.addEventListener('keydown', onDocKeyDown as unknown as EventListener)
    return () => document.removeEventListener('keydown', onDocKeyDown as unknown as EventListener)
  }, [navigationService])
}
