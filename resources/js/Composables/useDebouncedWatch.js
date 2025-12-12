import { watch } from "vue";

export function useDebouncedWatch(source, cb, delay = 500, options = {}) {
    let timeout;
    watch(
        source,
        (...args) => {
            clearTimeout(timeout);
            timeout = setTimeout(() => cb(...args), delay);
        },
        options
    );
}
