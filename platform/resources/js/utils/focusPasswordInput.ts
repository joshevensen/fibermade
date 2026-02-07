import { nextTick } from 'vue';

export async function focusPasswordInput(
    passwordRef: { value?: { $el?: HTMLElement } } | null,
): Promise<void> {
    if (!passwordRef?.value?.$el) {
        return;
    }

    await nextTick();
    const input = passwordRef.value.$el.querySelector(
        'input',
    ) as HTMLInputElement;
    input?.focus();
}
