{{--
    Logout icon button with a confirmation dialog.
    Usage: <x-logout-button />
    To use your own icon (e.g. an <svg>): <x-logout-button> <svg>...</svg> </x-logout-button>
--}}
<button type="button" title="Log out" onclick="document.getElementById('logout-dialog').showModal()"
    {{ $attributes->merge(['class' => 'text-gray-500 hover:text-gray-800 text-lg']) }}>
    @if ($slot->isEmpty())
        <i class="ti ti-logout"></i>
    @else
        {{ $slot }}
    @endif
</button>

<dialog id="logout-dialog" class="w-[92%] max-w-sm rounded-2xl p-0 shadow-xl backdrop:bg-black/40">
    <form method="POST" action="{{ route('logout') }}" class="p-6">
        @csrf

        <div class="flex items-start gap-4">
            <span
                class="w-11 h-11 shrink-0 rounded-full bg-red-50 text-red-600 flex items-center justify-center text-xl">
                <i class="ti ti-logout"></i>
            </span>
            <div>
                <h2 class="text-lg font-semibold text-[#12332d]">Log out?</h2>
                <p class="mt-1 text-sm text-gray-500">
                    Are you sure you want to log out? You will need to sign in again to continue.
                </p>
            </div>
        </div>

        <div class="mt-6 flex justify-end gap-3">
            <button type="button" autofocus onclick="this.closest('dialog').close()"
                class="rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-semibold text-[#12332d] hover:bg-gray-50">
                Cancel
            </button>
            <button type="submit"
                class="rounded-lg bg-red-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-red-700">
                Yes, log out
            </button>
        </div>
    </form>
</dialog>

<script>
    // Clicking the dark area outside the box also closes the dialog (Esc works by default)
    (function() {
        const dialog = document.getElementById('logout-dialog');
        dialog.addEventListener('click', (e) => {
            if (e.target === dialog) dialog.close();
        });
    })();
</script>
