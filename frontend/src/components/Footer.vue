<template>
  <footer class="border-t bg-gray-50 py-10 text-sm text-gray-500">
    <div class="mx-auto max-w-7xl px-4 sm:px-6">
      <div class="grid grid-cols-2 gap-8 md:grid-cols-4">
        <div>
          <p class="mb-3 font-semibold text-gray-700">NegosyoHub</p>
          <p class="text-xs leading-relaxed">
            Multi-sector marketplace connecting buyers with local Philippine
            businesses.
          </p>
        </div>

        <div>
          <p class="mb-3 font-semibold text-gray-700">Browse</p>
          <ul class="space-y-2">
            <li><a href="/stores" class="hover:text-brand-600">Stores</a></li>
            <li>
              <a href="/properties" class="hover:text-brand-600">Properties</a>
            </li>
          </ul>
        </div>

        <div>
          <p class="mb-3 font-semibold text-gray-700">Sellers</p>
          <ul class="space-y-2">
            <li>
              <a href="/register/store-owner/food" class="hover:text-brand-600"
                >Register Store</a
              >
            </li>
            <li>
              <a href="/store/dashboard" class="hover:text-brand-600"
                >Store Dashboard</a
              >
            </li>
          </ul>
        </div>

        <div>
          <p class="mb-3 font-semibold text-gray-700">Legal</p>
          <ul class="space-y-2">
            <li>
              <a href="/legal/terms" class="hover:text-brand-600"
                >Terms of Service</a
              >
            </li>
            <li>
              <a href="/legal/privacy" class="hover:text-brand-600"
                >Privacy Policy</a
              >
            </li>
          </ul>
        </div>
      </div>

      <p class="mt-8 text-center text-xs text-gray-400">
        &copy; {{ new Date().getFullYear() }} NegosyoHub. All rights reserved.
      </p>
    </div>
  </footer>
</template>
