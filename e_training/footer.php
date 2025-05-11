</section>
    </main>
<script>
/* SIDEBAR TOGGLE - Updated to handle RTL properly */
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    sidebar.classList.toggle('active');
    sidebar.classList.toggle('sidebar-open');
    
    // Debug information
    console.log("Sidebar toggled");
    console.log("Current classes:", sidebar.className);
    console.log("Is RTL:", document.dir === 'rtl' || document.documentElement.dir === 'rtl');
}

/* PROFILE PHOTO UPLOAD LOGIC */
document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.getElementById('fileInput');
    const profileImage = document.getElementById('profileImage');
    const changePhotoBtn = document.getElementById('changePhotoBtn');
    const removePhotoBtn = document.getElementById('removePhotoBtn');
    
    // Only run this code if these elements exist on the page
    if (fileInput && profileImage && changePhotoBtn && removePhotoBtn) {
        // Load saved image from localStorage if any
        const savedProfileImage = localStorage.getItem('profileImage');
        if (savedProfileImage) {
            profileImage.src = savedProfileImage;
        }
        
        // Trigger hidden file input
        changePhotoBtn.addEventListener('click', () => {
            fileInput.click();
        });
        
        // Handle file selection
        fileInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    const base64Image = event.target.result;
                    profileImage.src = base64Image;
                    // Persist in localStorage
                    localStorage.setItem('profileImage', base64Image);
                };
                reader.readAsDataURL(file);
            }
        });
        
        // Remove photo
        removePhotoBtn.addEventListener('click', () => {
            profileImage.src = 'defaultProfile.png';
            fileInput.value = "";
            localStorage.removeItem('profileImage');
        });
    }
});

/* LANGUAGE SWITCHING */
function setLanguage(lang) {
    // Set cookie with language preference
    document.cookie = "lang=" + lang + "; path=/; max-age=31536000"; // 1 year expiration
    location.reload();
}

/* RTL FIX AND ADDITIONAL SUPPORT */
document.addEventListener('DOMContentLoaded', function() {
    // Fix for RTL sidebar positioning
    const isRTL = document.dir === 'rtl' || document.documentElement.dir === 'rtl' || document.documentElement.lang === 'ar';
    
    if (isRTL) {
        console.log("RTL mode detected, applying sidebar fix");
        
        // Force correct sidebar positioning for RTL
        const sidebar = document.getElementById('sidebar');
        if (sidebar) {
            // Remove any inline styles that might be interfering
            sidebar.style.right = '';
            sidebar.style.left = '-300px';
            
            // Force RTL-specific styles
            document.body.classList.add('rtl-forced');
            
            console.log("Sidebar position fixed for RTL");
        }
        
        // Apply any additional RTL-specific adjustments
        document.querySelectorAll('.text-end').forEach(el => {
            el.classList.remove('text-end');
            el.classList.add('text-start');
        });
        
        document.querySelectorAll('.ms-auto').forEach(el => {
            el.classList.remove('ms-auto');
            el.classList.add('me-auto');
        });
    }
    
    const sidebar = document.getElementById('sidebar');
    const hamburger = document.querySelector('.hamburger');
    
    // Close sidebar when clicking outside of it
    document.addEventListener('click', function(event) {
        // If sidebar is active and click is outside sidebar and not on hamburger
        if (sidebar && sidebar.classList.contains('active') && 
            !sidebar.contains(event.target) && 
            hamburger && event.target !== hamburger) {
            sidebar.classList.remove('active');
            sidebar.classList.remove('sidebar-open');
        }
    });
    
    // Add keyboard accessibility
    document.addEventListener('keydown', function(event) {
        // Close sidebar with Escape key
        if (event.key === 'Escape' && sidebar) {
            if (sidebar.classList.contains('active') || sidebar.classList.contains('sidebar-open')) {
                sidebar.classList.remove('active');
                sidebar.classList.remove('sidebar-open');
            }
        }
    });
});

function fixHamburgerPosition() {
    const isRTL = document.dir === 'rtl' || document.documentElement.dir === 'rtl' || document.documentElement.lang === 'ar';
    const hamburger = document.querySelector('.hamburger');
    
    if (hamburger && isRTL) {
        console.log("Fixing hamburger button position for RTL");
        hamburger.style.right = 'auto';
        hamburger.style.left = '20px';
        hamburger.style.position = 'absolute';
    } else if (hamburger) {
        console.log("Setting hamburger button position for LTR");
        hamburger.style.left = 'auto';
        hamburger.style.right = '20px';
        hamburger.style.position = 'absolute';
    }
}

// Call this function on page load
document.addEventListener('DOMContentLoaded', fixHamburgerPosition);

// Also call it whenever the language changes
function setLanguage(lang) {
    // Set cookie with language preference
    document.cookie = "lang=" + lang + "; path=/; max-age=31536000"; // 1 year expiration
    location.reload();
}
</script>

<style>
/* Last resort direct overrides */
body.rtl-forced .sidebar {
    left: -300px !important;
    right: auto !important;
}

body.rtl-forced .sidebar.active,
body.rtl-forced .sidebar.sidebar-open {
    left: 0 !important;
    right: auto !important;
}
</style>
</body>
</html>
