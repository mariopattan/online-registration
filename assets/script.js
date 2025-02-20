jQuery(document).ready(function($) {  
    $('#event-form').on('submit', function(e) {  
        e.preventDefault(); // Prevent default form submission  
  
        var formData = $(this).serialize(); // Serialize form data  
  
        $.ajax({  
            type: 'POST',  
            url: ajax_object.ajax_url,  
            data: formData + '&action=register_event',  
            success: function(response) {  
                if (response.success) {  
                    $('#form-message').html('<p>' + response.data + '</p>');  
                    $('#event-form')[0].reset(); // Reset the form  
                } else {  
                    $('#form-message').html('<p>' + response.data + '</p>');  
                }  
            },  
            error: function() {  
                $('#form-message').html('<p>There was an error processing your request. Please try again.</p>');  
            }  
        });  
    });  
});  
function updatePreview() {
    // Get the participant name from the input field  
    const participantName = document.querySelector('input[name="participant_name"]').value;
    // Update the preview text  
    document.getElementById('participant-name-preview').innerText = participantName;

    // Handle event logo preview  
    const eventLogoInput = document.querySelector('input[name="event_logo"]');
    const footerLogoInput = document.querySelector('input[name="footer_logo"]');

    // Clear previous logos  
    const ticketPreview = document.getElementById('ticket-preview');
    ticketPreview.innerHTML = '';

    // Add event logo if uploaded  
    if (eventLogoInput.files && eventLogoInput.files[0]) {
        const reader = new FileReader();
        reader.onload = function (e) {
            const img = document.createElement('img');
            img.src = e.target.result;
            img.style.width = '100%';
            img.style.height = 'auto';
            ticketPreview.appendChild(img);
        };
        reader.readAsDataURL(eventLogoInput.files[0]);
    }

    // Add footer logo if uploaded  
    if (footerLogoInput.files && footerLogoInput.files[0]) {
        const reader = new FileReader();
        reader.onload = function (e) {
            const img = document.createElement('img');
            img.src = e.target.result;
            img.style.width = '100%';
            img.style.height = 'auto';
            img.style.position = 'absolute';
            img.style.bottom = '0';
            img.style.left = '0';
            ticketPreview.appendChild(img);
        };
        reader.readAsDataURL(footerLogoInput.files[0]);
    }

    // Update QR code  
    const qrCodeImg = document.createElement('img');
    qrCodeImg.src = 'https://api.qrserver.com/v1/create-qr-code/?data=' + encodeURIComponent(participantName) + '&size=100x100';
    qrCodeImg.alt = 'QR Code';
    qrCodeImg.style.width = '100px';
    qrCodeImg.style.height = '100px';
    ticketPreview.appendChild(qrCodeImg);
}  
function generatePDF() {
    const form = document.querySelector('form');
    const formData = new FormData(form);
    formData.append('generate_pdf', true); // Add a flag to indicate PDF generation  

    fetch(ajax_object.ajax_url, {
        method: 'POST',
        body: formData,
    })
        .then(response => response.blob())
        .then(blob => {
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.style.display = 'none';
            a.href = url;
            a.download = 'e_ticket.pdf';
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
        })
        .catch(error => console.error('Error generating PDF:', error));
}  

document.addEventListener('DOMContentLoaded', function () {
    const editButtons = document.querySelectorAll('.edit-button');
    const editForm = document.getElementById('edit-form');

    editButtons.forEach(button => {
        button.addEventListener('click', function () {
            const row = button.closest('tr');
            const id = button.getAttribute('data-id');
            const nationality = row.cells[1].innerText;
            const eventType = row.cells[2].innerText;
            const subCategory = row.cells[3].innerText;
            const pricingType = row.cells[4].innerText;
            const startDate = row.cells[5].innerText;
            const endDate = row.cells[6].innerText;
            const price = row.cells[7].innerText;
            const currency = row.cells[8].innerText;

            document.getElementById('update_pricing_id').value = id;
            document.getElementById('nationality').value = nationality;
            document.getElementById('event_type').value = eventType;
            document.getElementById('sub_category').value = subCategory;
            document.getElementById('pricing_type').value = pricingType;
            document.getElementById('start_date').value = startDate;
            document.getElementById('end_date').value = endDate;
            document.getElementById('price').value = price;
            document.getElementById('currency').value = currency;

            editForm.style.display = 'block'; // Show the edit form  
        });
    });
});  
