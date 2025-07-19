document.addEventListener('DOMContentLoaded', function() {
    const selectElements = document.querySelectorAll('select');

    selectElements.forEach(function(selectElement) {
        selectElement.addEventListener('change', function(event) {
            console.log('selected:', event.target.value);
            if(event.target.value === 'add_custom') {
            document.getElementById('switch_add_profile').innerHTML = '<p>Enter a name for the new profile: <input type=\"text\" name=\"profile\" id=\"new_profile_name\" /><input type=\"hidden\" name=\"page\" value=\"qckply_builder\" /> <button type=\"submit\">Add</button></p>';
            return;
            }
            const parentDiv = event.target.closest('p');
            if (parentDiv) {
                const parentDivId = parentDiv.id;
                const parts = parentDivId.split('_');
                const nextID = parts[0] + '_' + (parseInt(parts[1]) + 1);
                console.log('Next ID:', nextID);
                const nextDiv = document.getElementById(nextID);
                nextDiv.classList.remove('hidden_item');
            }
        });
    });
});

document.addEventListener("DOMContentLoaded", function () {
  const overlay = document.getElementById("playground-overlay-message");
  const closeBtn = document.getElementById("playground-overlay-close");
  console.log('overlay',overlay);

  // Hide after 90 seconds (90000 ms)
  const timer = setTimeout(() => {
    overlay.classList.add("fade-out");
  }, 90000);

  // Manual close
  closeBtn.addEventListener("click", () => {
    clearTimeout(timer);
    overlay.classList.add("fade-out");
  });
});
