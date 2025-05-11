// Function to toggle sidebar visibility
function toggleSidebar() {
  const sidebar = document.getElementById("sidebar")
  const content = document.querySelector(".content")

  if (sidebar) {
    sidebar.classList.toggle("active")

    if (content) {
      content.classList.toggle("sidebar-open")
    }
  }
}

// Function to set language and store in cookie
function setLanguage(lang) {
  document.cookie = `lang=${lang}; path=/; max-age=31536000` // 1 year
  location.reload()
}

// Close sidebar when clicking outside of it
document.addEventListener("DOMContentLoaded", () => {
  const sidebar = document.getElementById("sidebar")
  const hamburger = document.querySelector(".hamburger")

  if (sidebar && hamburger) {
    document.addEventListener("click", (event) => {
      // If sidebar is open and click is outside sidebar and not on hamburger
      if (sidebar.classList.contains("active") && !sidebar.contains(event.target) && event.target !== hamburger) {
        toggleSidebar()
      }
    })
  }
})
