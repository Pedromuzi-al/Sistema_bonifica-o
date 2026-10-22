<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-4 py-2 bg-[#0faddf] border border-transparent rounded-md font-semibold text-xs text-slate-950 uppercase tracking-widest hover:bg-[#0b96c2] focus:bg-[#0b96c2] active:bg-[#087ca8] focus:outline-hidden focus:ring-2 focus:ring-sky-500 focus:ring-offset-2 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
