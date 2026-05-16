import '@css/app.css';
import { buildDiContainer, ContainerContext } from '@ts/container';
import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import App from './components/app.tsx';

// * Bootstrap SM

const container = buildDiContainer()

const rootEl = document.getElementById('file-manager')

if (rootEl === null) {
  throw new Error('Root element not found');
}

createRoot(rootEl).render(
  <StrictMode>
    <ContainerContext.Provider value={container}>
      <App />
    </ContainerContext.Provider>
  </StrictMode>,
)
