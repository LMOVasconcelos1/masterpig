const fs = require('fs');

const path = 'c:/Users/lmova/GitHub/masterpig/resources/views/layouts/dashboard.blade.php';
let content = fs.readFileSync(path, 'utf8');

// Container Styles
content = content.replace(
    'class="fixed inset-y-0 left-0 z-40 w-72 max-w-[85vw] overflow-y-auto bg-primary-800 text-white shadow-2xl lg:hidden"',
    'class="fixed inset-y-0 left-0 z-40 w-72 max-w-[85vw] overflow-y-auto bg-white text-gray-800 border-r border-gray-100 shadow-2xl lg:hidden"'
);

content = content.replace(
    'class="sidebar-transition relative z-20 flex-shrink-0 hidden h-full overflow-y-auto bg-primary-800 text-white lg:block shadow-xl"',
    'class="sidebar-transition relative z-20 flex-shrink-0 hidden h-full overflow-y-auto bg-white text-gray-800 border-r border-gray-100 lg:block shadow-xl"'
);

// Headers and Footers
content = content.replace(
    '<div class="flex items-center justify-between h-16 px-4 bg-primary-900">',
    '<div class="flex items-center justify-between h-16 px-4 bg-white border-b border-gray-100">'
);
content = content.replace(
    '<div class="flex items-center justify-center h-16 px-4 bg-primary-900">',
    '<div class="flex items-center justify-center h-16 px-4 bg-white border-b border-gray-100">'
);
content = content.replace(
    '<div class="p-4 bg-primary-900">',
    '<div class="p-4 bg-white border-t border-gray-100">'
);

// Close Button and Chevron
content = content.replace(
    'class="p-2 rounded-lg text-primary-100 hover:bg-primary-800"',
    'class="p-2 rounded-lg text-gray-400 hover:bg-gray-50 hover:text-gray-600"'
);
content = content.replace(
    'class="flex items-center justify-center w-full px-4 py-2 text-primary-100 transition-colors rounded-lg hover:bg-primary-800"',
    'class="flex items-center justify-center w-full px-4 py-2 text-gray-500 transition-colors rounded-lg hover:bg-gray-50 hover:text-gray-700"'
);

// Logo/Icons and Texts
content = content.replace(
    /<i class="fa-solid fa-piggy-bank text-2xl text-primary-300"><\/i>/g,
    '<i class="fa-solid fa-piggy-bank text-2xl text-primary-600"></i>'
);
content = content.replace(
    '<span class="text-xl font-bold tracking-wider uppercase">Sui Control</span>',
    '<span class="text-xl font-bold tracking-wider uppercase text-gray-900">Sui Control</span>'
);
content = content.replace(
    '<span x-show="sidebarOpen" class="text-xl font-bold tracking-wider uppercase transition-opacity duration-300">Sui Control</span>',
    '<span x-show="sidebarOpen" class="text-xl font-bold tracking-wider uppercase text-gray-900 transition-opacity duration-300">Sui Control</span>'
);

// Group headers
content = content.replace(
    /class="flex items-center justify-between w-full px-4 py-3 text-primary-100 transition-colors rounded-lg hover:bg-primary-700 hover:text-white group"/g,
    'class="flex items-center justify-between w-full px-4 py-3 text-gray-600 transition-colors rounded-lg hover:bg-gray-50 hover:text-gray-900 group"'
);

// Vertical Submenu line
content = content.replace(
    /border-l border-primary-600/g,
    'border-l border-gray-200'
);

// Links
content = content.replace(
    /'text-white font-bold bg-primary-700\/50'/g,
    "'text-primary-700 font-bold bg-primary-50'"
);
content = content.replace(
    /'text-primary-300'/g,
    "'text-gray-500'"
);
content = content.replace(
    /transition-colors rounded-lg hover:bg-primary-700 hover:text-white/g,
    "transition-colors rounded-lg hover:bg-gray-50 hover:text-primary-600"
);

fs.writeFileSync(path, content);
console.log("Sidebar updated!");
