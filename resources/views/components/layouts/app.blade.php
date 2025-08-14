<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>L’Atome Français - Suivi de la production électro-nucléaire française heure par heure</title>

        @livewireStyles
        @livewireScripts
        
        <!-- Styles / Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="w-screen h-screen flex flex-col md:flex-row gap-2 bg-slate-200 dark:bg-slate-700 p-2">
        <nav class="md:w-16 h-16 md:h-auto flex md:flex-col items-center justify-between shrink-0 bg-white dark:bg-slate-800 rounded-md px-4 md:px-0 md:py-4">
            <a href="{{ route('home') }}" wire:navigate>
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" class="w-11 h-11 hover:scale-95 transition-transform duration-200">
                    <linearGradient id="gradient-a" gradientUnits="userSpaceOnUse" x1="256" x2="256" y1="512" y2="-85.333">
                        <stop offset="0" stop-color="#c3ffe8"></stop>
                        <stop offset=".9973" stop-color="#f0fff4"></stop>
                    </linearGradient>
                    <linearGradient id="gradient-b" gradientUnits="userSpaceOnUse" x1="256" x2="256" y1="407" y2="105">
                        <stop offset="0" stop-color="#9cffac"></stop>
                        <stop offset="1" stop-color="#00b59c"></stop>
                    </linearGradient>
                    <g>
                        <circle cx="256" cy="256" r="256" fill="url(#gradient-b)"></circle>
                        <path d="m373.969 247.742c4.886 0 8.848 3.961 8.848 8.848 0 4.886-3.961 8.848-8.848 8.848h-2.949v17.695h-82.578v-35.391zm-230.039-17.695h2.949v17.695h123.867v-17.695h2.949c4.886 0 8.848-3.961 8.848-8.848s-3.961-8.848-8.848-8.848h-129.765c-4.886 0-8.848 3.961-8.848 8.848s3.961 8.848 8.848 8.848zm76.718-36.57h82.841c19.546 0 35.391-15.845 35.391-35.391h-93.195c-15.477 0-28.634 9.935-33.441 23.776-1.977 5.692 2.379 11.615 8.404 11.615zm176.914-70.782c0-9.773-7.922-17.695-17.695-17.695h-82.578c-19.546 0-35.391 15.845-35.391 35.391h117.969c9.773 0 17.695-7.923 17.695-17.696zm.59 266.61h-7.551c-12.825-20.785-19.582-44.567-19.582-69.077v-19.399h-82.578v5.295c0 27.63 8.488 54.118 24.548 76.601l17.34 24.275h67.823c4.886 0 8.848-3.961 8.848-8.848 0-4.886-3.961-8.847-8.848-8.847zm-99.562 3.705 9.993 13.99h-194.735c-4.886 0-8.848-3.961-8.848-8.848 0-4.886 3.961-8.848 8.848-8.848h7.743c16.555-24.686 25.288-53.345 25.288-83.181v-40.686h123.867v40.686c0 31.34 9.629 61.385 27.844 86.887zm-97.44-56.496c-2.443-4.232-7.854-5.682-12.086-3.238l-15.324 8.848c-4.232 2.443-5.682 7.854-3.238 12.086s7.854 5.682 12.086 3.238l15.324-8.848c4.231-2.444 5.681-7.855 3.238-12.086zm16.51-22.119v-17.695c0-4.886-3.961-8.848-8.848-8.848-4.886 0-8.848 3.961-8.848 8.848v17.695c0 4.886 3.961 8.848 8.848 8.848 4.887-.001 8.848-3.962 8.848-8.848zm26.225 27.728-15.324-8.848c-4.232-2.443-9.643-.993-12.086 3.238s-.993 9.643 3.238 12.086l15.324 8.848c4.232 2.443 9.643.993 12.086-3.238s.994-9.643-3.238-12.086z" class="fill-white dark:fill-slate-800"></path>
                    </g>
                </svg>
            </a>
            <ul class="flex md:flex-col gap-2 md:mt-auto">
                <li x-data="{ open: true }" class="relative" x-on:click.away="open = false">
                    <button class="w-8 h-8 flex items-center justify-center" type="button" x-on:click="open = true">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="w-6 h-6 fill-slate-500 hover:fill-slate-800 dark:hover:fill-slate-200 transition-colors duration-200"><!--!Font Awesome Pro v7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2025 Fonticons, Inc.--><path d="M296 552L296 576L344 576L344 472L296 472L296 552zM296 144L296 168L344 168L344 64L296 64L296 144zM156 122L122 156C129.5 163.5 148.4 182.4 178.6 212.6L195.6 229.6L229.5 195.7C222 188.2 203.1 169.3 172.9 139.1L156 122zM444.5 410.5L410.6 444.4L484.1 517.9L518 484L444.5 410.5zM64 296L64 344L168 344L168 296L64 296zM472 296L472 344L576 344L576 296L472 296zM122 484L156 518C163.5 510.5 182.4 491.6 212.6 461.4L229.6 444.4L195.7 410.5C188.2 418 169.3 436.9 139.1 467.1L122 484zM410.5 195.5L444.4 229.4C451.9 221.9 470.8 203 501 172.8L518 155.9L484 122C476.5 129.5 457.6 148.4 427.4 178.6L410.4 195.6zM320 432C381.9 432 432 381.9 432 320C432 258.1 381.9 208 320 208C258.1 208 208 258.1 208 320C208 381.9 258.1 432 320 432z"/></svg>
                    </button>
                    <div class="absolute -left-2 -top-3 md:-top-1.5 w-10 md:w-auto h-auto md:h-10 flex flex-col md:flex-row items-center gap-2 bg-slate-200 dark:bg-slate-700 border-2 border-slate-200 dark:border-slate-800 rounded-full md:px-3 py-3 md:py-0 z-1000" x-show="open" x-cloak>
                        <button type="button" x-on:click="localStorage.setItem('theme', 'dark'); document.documentElement.classList.add('dark'); open = false;">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="w-6 h-6 fill-slate-500 dark:fill-slate-300 hover:fill-slate-800 dark:hover:fill-slate-200 transition-colors duration-200"><!--!Font Awesome Pro v7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2025 Fonticons, Inc.--><path d="M512 32L528 80L576 96L528 112L512 160L496 112L448 96L496 80L512 32zM378.7 147.1C302.5 166 246 234.9 246 317C246 413.6 324.3 492 421 492C437.2 492 452.9 489.8 467.8 485.7C427 540.5 361.7 576 288 576C164.3 576 64 475.7 64 352C64 228.3 164.3 128 288 128C320.3 128 351 134.8 378.7 147.1zM448 416L419.2 332.8L336 304L419.2 275.2L448 192L476.8 275.2L560 304L476.8 332.8L448 416z"/></svg>
                        </button>
                        <button type="button" x-on:click="localStorage.setItem('theme', 'light'); document.documentElement.classList.remove('dark'); open = false;">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="w-6 h-6 fill-slate-500 dark:fill-slate-300 hover:fill-slate-800 dark:hover:fill-slate-200 transition-colors duration-200"><!--!Font Awesome Pro v7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2025 Fonticons, Inc.--><path d="M296 552L296 576L344 576L344 472L296 472L296 552zM296 144L296 168L344 168L344 64L296 64L296 144zM156 122L122 156C129.5 163.5 148.4 182.4 178.6 212.6L195.6 229.6L229.5 195.7C222 188.2 203.1 169.3 172.9 139.1L156 122zM444.5 410.5L410.6 444.4L484.1 517.9L518 484L444.5 410.5zM64 296L64 344L168 344L168 296L64 296zM472 296L472 344L576 344L576 296L472 296zM122 484L156 518C163.5 510.5 182.4 491.6 212.6 461.4L229.6 444.4L195.7 410.5C188.2 418 169.3 436.9 139.1 467.1L122 484zM410.5 195.5L444.4 229.4C451.9 221.9 470.8 203 501 172.8L518 155.9L484 122C476.5 129.5 457.6 148.4 427.4 178.6L410.4 195.6zM320 432C381.9 432 432 381.9 432 320C432 258.1 381.9 208 320 208C258.1 208 208 258.1 208 320C208 381.9 258.1 432 320 432z"/></svg>
                        </button>
                        <button type="button" x-on:click="localStorage.removeItem('theme'); document.documentElement.classList.toggle('dark', window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches); open = false;">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="w-6 h-6 fill-slate-500 dark:fill-slate-300 hover:fill-slate-800 dark:hover:fill-slate-200 transition-colors duration-200"><!--!Font Awesome Pro v7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2025 Fonticons, Inc.--><path d="M512 160L512 416L128 416L128 160L512 160zM128 96L64 96L64 480L272 480L256 528L160 528L160 576L480 576L480 528L384 528L368 480L576 480L576 96L128 96z"/></svg>
                        </button>
                    </div>
                </li>
                <li>
                    <a class="w-8 h-8 flex items-center justify-center" href="https://github.com/production-nucleaire/app" target="_blank" rel="noopener noreferrer">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="w-6 h-6 fill-slate-500 hover:fill-slate-800 dark:hover:fill-slate-200 transition-colors duration-200"><!--!Font Awesome Free v7.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M237.9 461.4C237.9 463.4 235.6 465 232.7 465C229.4 465.3 227.1 463.7 227.1 461.4C227.1 459.4 229.4 457.8 232.3 457.8C235.3 457.5 237.9 459.1 237.9 461.4zM206.8 456.9C206.1 458.9 208.1 461.2 211.1 461.8C213.7 462.8 216.7 461.8 217.3 459.8C217.9 457.8 216 455.5 213 454.6C210.4 453.9 207.5 454.9 206.8 456.9zM251 455.2C248.1 455.9 246.1 457.8 246.4 460.1C246.7 462.1 249.3 463.4 252.3 462.7C255.2 462 257.2 460.1 256.9 458.1C256.6 456.2 253.9 454.9 251 455.2zM316.8 72C178.1 72 72 177.3 72 316C72 426.9 141.8 521.8 241.5 555.2C254.3 557.5 258.8 549.6 258.8 543.1C258.8 536.9 258.5 502.7 258.5 481.7C258.5 481.7 188.5 496.7 173.8 451.9C173.8 451.9 162.4 422.8 146 415.3C146 415.3 123.1 399.6 147.6 399.9C147.6 399.9 172.5 401.9 186.2 425.7C208.1 464.3 244.8 453.2 259.1 446.6C261.4 430.6 267.9 419.5 275.1 412.9C219.2 406.7 162.8 398.6 162.8 302.4C162.8 274.9 170.4 261.1 186.4 243.5C183.8 237 175.3 210.2 189 175.6C209.9 169.1 258 202.6 258 202.6C278 197 299.5 194.1 320.8 194.1C342.1 194.1 363.6 197 383.6 202.6C383.6 202.6 431.7 169 452.6 175.6C466.3 210.3 457.8 237 455.2 243.5C471.2 261.2 481 275 481 302.4C481 398.9 422.1 406.6 366.2 412.9C375.4 420.8 383.2 435.8 383.2 459.3C383.2 493 382.9 534.7 382.9 542.9C382.9 549.4 387.5 557.3 400.2 555C500.2 521.8 568 426.9 568 316C568 177.3 455.5 72 316.8 72zM169.2 416.9C167.9 417.9 168.2 420.2 169.9 422.1C171.5 423.7 173.8 424.4 175.1 423.1C176.4 422.1 176.1 419.8 174.4 417.9C172.8 416.3 170.5 415.6 169.2 416.9zM158.4 408.8C157.7 410.1 158.7 411.7 160.7 412.7C162.3 413.7 164.3 413.4 165 412C165.7 410.7 164.7 409.1 162.7 408.1C160.7 407.5 159.1 407.8 158.4 408.8zM190.8 444.4C189.2 445.7 189.8 448.7 192.1 450.6C194.4 452.9 197.3 453.2 198.6 451.6C199.9 450.3 199.3 447.3 197.3 445.4C195.1 443.1 192.1 442.8 190.8 444.4zM179.4 429.7C177.8 430.7 177.8 433.3 179.4 435.6C181 437.9 183.7 438.9 185 437.9C186.6 436.6 186.6 434 185 431.7C183.6 429.4 181 428.4 179.4 429.7z"/></svg>
                    </a>
                </li>
            </ul>
        </nav>
        {{ $slot }}
    </body>
</html>
