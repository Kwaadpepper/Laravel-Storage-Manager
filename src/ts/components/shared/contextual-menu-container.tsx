import { useContextualMenuStore } from "@ts/stores";
import { FocusTrap } from 'focus-trap-react';
import { useEffect, useMemo, useRef } from "react";

interface ContextualMenuContainerProps {
}

export function ContextualMenuContainer(_: Readonly<ContextualMenuContainerProps>) {
  const popoverRef = useRef<HTMLDivElement | null>(null)
  const { id, anchorName, visible, entries, closeMenu } = useContextualMenuStore()
  const isActive = visible && entries.length > 0

  const focusTrapOptions = useMemo(() => ({
    escapeDeactivates: true,
    onDeactivate: closeMenu,
    clickOutsideDeactivates: true,
    returnFocusOnDeactivate: true,
    fallbackFocus: () => popoverRef.current ?? document.body,
  }), [closeMenu])

  useEffect(() => {
    const popover = popoverRef.current
    if (!popover) {
      return
    }

    const isOpen = popover.matches(':popover-open')
    if (isActive && !isOpen) { popover.showPopover(); return }
    if (!isActive && isOpen) popover.hidePopover()
  }, [isActive])

  return (
    <FocusTrap active={isActive} focusTrapOptions={focusTrapOptions}>
      <div
        ref={popoverRef}
        id={id}
        tabIndex={-1}
        style={{
          position: 'fixed',
          positionAnchor: anchorName,
          top: 'calc(anchor(bottom) + 0.25rem)',
          left: 'anchor(left)'
        }}
        className="dropdown menu z-50 w-52 rounded-box border border-base-300 bg-base-100/95 shadow-lg ring-1 ring-base-content/10 backdrop-blur-sm"
        popover="manual"
      >
        <ul>
          {entries.map((item) => (
            "separator" in item ? (
              <li key={item.id} className="divider" />
            ) : (
              <li key={item.id}>
                <button
                  className="btn btn-ghost"
                  title={item.label}
                  onClick={() => { item.onClick(); closeMenu(); }}
                >{item.label}</button>
              </li>
            )
          ))}
        </ul>
      </div>
    </FocusTrap>
  )
}
