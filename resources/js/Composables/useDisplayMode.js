import { ref, watch } from 'vue'

const STORAGE_KEY = 'supervisor_display_mode'

// Estado global compartido entre todas las instancias
const displayMode = ref(localStorage.getItem(STORAGE_KEY) || 'normal')

// Watch para persistir cambios en localStorage
watch(displayMode, (newMode) => {
    localStorage.setItem(STORAGE_KEY, newMode)
})

export function useDisplayMode() {
    const toggleMode = () => {
        displayMode.value = displayMode.value === 'normal' ? 'tv' : 'normal'
    }

    const setMode = (mode) => {
        displayMode.value = mode
    }

    return {
        displayMode,
        toggleMode,
        setMode,
        isTVMode: () => displayMode.value === 'tv',
        isNormalMode: () => displayMode.value === 'normal'
    }
}
