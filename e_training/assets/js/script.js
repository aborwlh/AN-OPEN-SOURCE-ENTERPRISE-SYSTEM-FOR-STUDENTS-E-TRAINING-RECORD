document.querySelector(".auth-form").addEventListener("submit", function (event) {
    event.preventDefault(); // يمنع إرسال النموذج بشكل افتراضي
  
    // إظهار رسالة التأكيد
    document.getElementById("confirmation-message").classList.remove("hidden");
  
    // تعطيل الزر لمنع الإرسال المتكرر
    document.querySelector(".btn-primary").disabled = true;
  });
  