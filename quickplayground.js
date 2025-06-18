document.addEventListener('DOMContentLoaded', function() {
    const selectElements = document.querySelectorAll('select');

    selectElements.forEach(function(selectElement) {
        selectElement.addEventListener('change', function(event) {
            console.log('selected:', event.target.value);
            if(event.target.value === 'add_custom') {
            document.getElementById('switch_add_profile').innerHTML = '<p>Enter a name for the new profile: <input type=\"text\" name=\"profile\" id=\"new_profile_name\" /> <button type=\"submit\">Add</button></p>';
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
