@props(['disabled' => false])

<input @disabled($disabled)  {{ $attributes->merge([
    'class' => '
        w-full rounded-xl
        border border-black/10 dark:border-white/15
        bg-white dark:bg-[#0f0f0f]
        text-sm text-[#1b1b18] dark:text-[#EDEDEC]
        placeholder-black/40 dark:placeholder-white/40

        focus:outline-none
        focus:border-black/30 dark:focus:border-white/30
        focus:ring-0

        transition
    '
]) }}>