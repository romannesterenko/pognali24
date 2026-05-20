/// <reference types="vite/client" />

interface ImportMetaEnv {
    readonly VITE_API_URL: string
    readonly VITE_WS_URL: string
    readonly VITE_REVERB_APP_KEY: string
    readonly VITE_REVERB_APP_ID: string
}

interface ImportMeta {
    readonly env: ImportMetaEnv
}