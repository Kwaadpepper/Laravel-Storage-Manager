import { useContainer } from "@ts/container";
import { useFileManagerStore, useSearchStore } from "@ts/stores";
import { Path } from "@ts/types";
import { Filter, Search, X } from "lucide-react";
import { FormEvent, useRef, useState, useEffect } from "react";

function parseSizeToUnit(sizeInBytes?: number): { value: string, unit: number } {
  if (sizeInBytes === undefined || isNaN(sizeInBytes)) return { value: '', unit: 1 }
  if (sizeInBytes === 0) return { value: '0', unit: 1 }

  if (sizeInBytes % (1024 ** 3) === 0) return { value: (sizeInBytes / (1024 ** 3)).toString(), unit: 1024 ** 3 }
  if (sizeInBytes % (1024 ** 2) === 0) return { value: (sizeInBytes / (1024 ** 2)).toString(), unit: 1024 ** 2 }
  if (sizeInBytes % 1024 === 0) return { value: (sizeInBytes / 1024).toString(), unit: 1024 }
  
  return { value: sizeInBytes.toString(), unit: 1 }
}

export function SearchBar() {
  const container = useContainer()
  const navigationService = container.resolve('navigationService')
  const currentPath = useFileManagerStore(state => state.currentPath)
  const isSearchMode = currentPath === '/:search'
  
  const { query, extension, minSize, maxSize } = useSearchStore()

  const [localQuery, setLocalQuery] = useState('')
  const [localExt, setLocalExt] = useState('')
  const [localMinSize, setLocalMinSize] = useState('')
  const [localMaxSize, setLocalMaxSize] = useState('')
  const [localMinUnit, setLocalMinUnit] = useState(1)
  const [localMaxUnit, setLocalMaxUnit] = useState(1)

  useEffect(() => {
    if (isSearchMode) {
      // eslint-disable-next-line react-hooks/set-state-in-effect
      setLocalQuery(query || '')
      setLocalExt(extension || '')

      const parsedMin = parseSizeToUnit(minSize)
      setLocalMinSize(parsedMin.value)
      setLocalMinUnit(parsedMin.unit)

      const parsedMax = parseSizeToUnit(maxSize)
      setLocalMaxSize(parsedMax.value)
      setLocalMaxUnit(parsedMax.unit)
    } else {
      setLocalQuery('')
      setLocalExt('')
      setLocalMinSize('')
      setLocalMaxSize('')
      setLocalMinUnit(1)
      setLocalMaxUnit(1)
    }
  }, [isSearchMode, query, extension, minSize, maxSize])

  const dropdownRef = useRef<HTMLDetailsElement>(null)

  function onSearch(e: FormEvent) {
    e.preventDefault()
    
    if (dropdownRef.current) {
      dropdownRef.current.removeAttribute('open')
    }

    if (!localQuery && !localExt && !localMinSize && !localMaxSize) {
      if (isSearchMode) {
        navigationService.navigatePrevious()
      }
      return
    }

    const searchParams = new URLSearchParams()
    if (localQuery) searchParams.set('q', localQuery)
    if (localExt) searchParams.set('ext', localExt)
    
    if (localMinSize) {
      const minBytes = Math.floor(parseFloat(localMinSize) * localMinUnit)
      if (!isNaN(minBytes)) searchParams.set('minSize', minBytes.toString())
    }
    
    if (localMaxSize) {
      const maxBytes = Math.floor(parseFloat(localMaxSize) * localMaxUnit)
      if (!isNaN(maxBytes)) searchParams.set('maxSize', maxBytes.toString())
    }

    navigationService.navigateTo('/:search' as Path, undefined, searchParams)
  }

  function onResetClick() {
    if (isSearchMode) {
      navigationService.navigatePrevious()
    }
  }

  return (
    <div className="flex items-center gap-1 relative">
      <form onSubmit={onSearch} className="join flex items-center">
        <input
          type="text"
          placeholder="Rechercher..."
          className="input input-bordered input-sm join-item w-40 md:w-64"
          value={localQuery}
          onChange={(e) => setLocalQuery(e.target.value)}
        />
        <details className="dropdown dropdown-bottom dropdown-end" ref={dropdownRef}>
          <summary 
            className="btn btn-sm join-item border-l-0"
          >
            <Filter size={16} className={localExt || localMinSize || localMaxSize ? 'text-primary' : ''} />
          </summary>
          <ul className="dropdown-content z-[100] menu p-4 shadow bg-base-100 rounded-box w-[22rem] mt-1">
            <h3 className="font-bold mb-3">Filtres avancés</h3>
            
            <div className="form-control mb-2">
              <label className="label py-1"><span className="label-text">Extension</span></label>
              <input 
                type="text" 
                placeholder="ex: pdf, jpg" 
                className="input input-bordered input-sm w-full" 
                value={localExt}
                onChange={(e) => setLocalExt(e.target.value.replace('.', ''))}
              />
            </div>

            <div className="form-control mb-2">
              <label className="label py-1"><span className="label-text">Taille min</span></label>
              <div className="join w-full">
                <input 
                  type="number" 
                  min="0"
                  step="any"
                  className="input input-bordered input-sm join-item w-full" 
                  value={localMinSize}
                  onChange={(e) => setLocalMinSize(e.target.value)}
                />
                <select 
                  className="select select-bordered select-sm join-item"
                  value={localMinUnit}
                  onChange={(e) => setLocalMinUnit(Number(e.target.value))}
                >
                  <option value={1}>Octets</option>
                  <option value={1024}>Ko</option>
                  <option value={1024 * 1024}>Mo</option>
                  <option value={1024 * 1024 * 1024}>Go</option>
                </select>
              </div>
            </div>

            <div className="form-control mb-4">
              <label className="label py-1"><span className="label-text">Taille max</span></label>
              <div className="join w-full">
                <input 
                  type="number" 
                  min="0"
                  step="any"
                  className="input input-bordered input-sm join-item w-full" 
                  value={localMaxSize}
                  onChange={(e) => setLocalMaxSize(e.target.value)}
                />
                <select 
                  className="select select-bordered select-sm join-item"
                  value={localMaxUnit}
                  onChange={(e) => setLocalMaxUnit(Number(e.target.value))}
                >
                  <option value={1}>Octets</option>
                  <option value={1024}>Ko</option>
                  <option value={1024 * 1024}>Mo</option>
                  <option value={1024 * 1024 * 1024}>Go</option>
                </select>
              </div>
            </div>

            <button type="button" className="btn btn-primary btn-sm w-full" onClick={onSearch}>
              Appliquer
            </button>
          </ul>
        </details>
        <button type="submit" className="btn btn-primary btn-sm join-item" onClick={onSearch}>
          <Search size={16} />
        </button>
      </form>
      
      {isSearchMode && (
        <button className="btn btn-ghost btn-sm btn-circle ml-1 text-error" onClick={onResetClick} title="Fermer la recherche">
          <X size={16} />
        </button>
      )}
    </div>
  )
}
