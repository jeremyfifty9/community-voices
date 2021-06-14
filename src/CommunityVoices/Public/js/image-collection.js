function sortCheckboxes($e) {
  var sorted = [];
  $e.children().each(function(i) {
    var $input = $(this).children('input');
    var $label = $(this).children('label');
    sorted.push({
      id: $input.attr('id'),
      value: $input.val(),
      label: $label.text(),
      name: $input.attr('name'),
      form: $input.attr('form'),
      checked: $input.prop('checked')
    });
  });
  sorted.sort(function(a, b) {
    if (a.checked && b.checked) {
      return cmp_str(a.label, b.label);
    }
    if (a.checked) {
      return -1;
    }
    if (b.checked) {
      return 1;
    }
    return cmp_str(a.label, b.label);
  });
  var html = '';
  for (var i = 0; i < sorted.length; i++) {
    var checked = (sorted[i].checked) ? 'checked="checked"' : '';
    html += '<div class="form-check"><input ' + checked +
        ' value="' + sorted[i].value +
        '" class="form-check-input" type="checkbox" name="' + sorted[i].name +
        (sorted[i].form ? '" form="' + sorted[i].form : '') +
        '" id="' + sorted[i].id +
        '"><label for="' + sorted[i].id +
        '" class="form-check-label">' + sorted[i].label +
        '</label></div>';
  }
  return html;
}
function cmp_str(a, b) {
  if(a < b) return -1;
  if(a > b) return 1;
  return 0;
}

$('.sorted-checkboxes').each(function(i, container){
  $(container).each(function(j, checkboxes) {
    var $checkboxes = $(checkboxes);
    $checkboxes.html(sortCheckboxes($checkboxes)); // sort once initially
    $checkboxes.on("change", function() {
      $(this).html(sortCheckboxes($(this))); // resort every time checkbox checked
    });
  });
});

$('.delete-form').on('submit', function(e) {
  e.preventDefault();
  var form = $(this);
  var action = form.attr('action');
  $('#alert').addClass('alert-danger').removeClass('d-none alert-success');
  $('#alert-content').html('<strong>Stop!</strong> Are you sure you want to delete this item?');
  var btn = document.createElement("button");
  btn.appendChild(document.createTextNode("Delete"));
  btn.className = "btn btn-outline-danger btn-sm float-right";
  btn.style.position = 'relative';
  btn.style.bottom = '3px';
  btn.addEventListener('click', function() {
    form.parent().parent().remove();
    $('#alert').removeClass('alert-danger').addClass('alert-success');
    $('#alert-content').text("Success!");
    setTimeout(function () {
        $('#alert').removeClass('alert-success').addClass('d-none');
    }, 2000);
    $.post(action);
  });
  $('#alert-content').append(btn);
});

// form is the form that we are submitting (an edit form)
// whatUpdated is a string of what we will say we updated
function submitEdit (form, whatUpdated) {
    var data = $(form).serializeArray();

    // Force our forms to include a tags attribute, even if empty.
    // https://stackoverflow.com/a/17809177/2397924
    var names = data.map(function (d) {
        return d.name;
    });

    if (!names.includes("tags[]")) {
        data.push({
            name: "tags[]",
            value: ""
        });
    }

    $('#alert').addClass('alert-success').removeClass('d-none alert-danger');
    $('#alert-content').text('Updating...');

    $.ajax({
      url : $(form).attr('action') || window.location.pathname,
      type: $(form).attr('method') || "POST",
      data: $.param(data),
      success: function (data) {
          $('#alert-content').text('Updated ' + whatUpdated);
      },
      error: function (data) {
          $('#alert').addClass('alert-danger').removeClass('d-none alert-success');
          $('#alert-content').text('Failed to update ' + whatUpdated);
      }
    });
}

$('.edit-form').on('submit', function(e) {
  e.preventDefault();

  submitEdit(this, this.elements.title.value);
});

$('#file').on('change', function(e) {
	var names = $.map($(this).prop('files'), function(val) { return val.name; });
	var namesList = 'Selected ' + names.join(', ');

	$('#fileList').text(namesList);

	var file = this.files[0];
	var reader = new FileReader();

	reader.onloadend = function() {
    // https://stackoverflow.com/a/7585267/2397924
    // https://stackoverflow.com/a/33901415/2397924
		var exif = EXIF.readFromBinaryFile(this.result);

		// exif.DateTime will be the date of photo taken, set by a camera
		// if it does not exist, this may be a screenshot
		// we will default to the file time if it is set
		// to anything other than 0
		var date = exif.DateTime || exif.FileDateTime;

		// date may equal 0, but we don't want to fill in
		// with a value of 0
		if (date != 0)
			$('#dateTaken').val(date);

		$('#title').val(names[0]);
	};

	reader.readAsArrayBuffer(file);
});

function submitAll() {
    $("#form-table form").filter(".edit-form").each (function () {
        submitEdit(this, 'all');
    });
}

$("body").on('click', '#fileUploadButton', function (c)  {
	$('#csvFile').trigger("click");
});

$("body").on("change","#csvFile",function(){
  let myForm = document.getElementById('batchUploadForm');
  $('#alert').addClass('alert-success').removeClass('d-none alert-danger');
  $('#alert-content').text('Updating...');
	$.ajax({
    url : $("#batchUploadForm").attr('action'),
    type: $("#batchUploadForm").attr('method'),
    data: new FormData(myForm),
    processData: false,
    contentType: false,
    cache: false,
    enctype: 'multipart/form-data',
    success: function (textStatus, status) {
      $('#alert-content').text('Batch Upload Completed! Refresh to see changes');
    },
    error: function(xhr, textStatus, error) {
      $('#alert').addClass('alert-danger').removeClass('d-none alert-success');
      $('#alert-content').text('Batch Upload Failed!');
    }
  });
});

$("body").on("click","#metadataChooseButton",function(){
  if(! $("#metadataUploadForm td").length) {
    $("#metadataUploadForm").append("<tr><td><div style='font-size:12px;'><input type='text' name='fields[]' placeholder='metadata'></input></div></td><td><div style='font-size:8px;'><button class='addMetadata'>Add</button></div></td></tr>");
    $("#metadataUploadForm").append("<div style='font-size:12px;'><button id='metadataSubmit'>Submit</button></div>");
  } else {
    $("#metadataUploadForm tr").remove();
    $("#metadataUploadForm div").remove();
  }
});

$("body").on("click","#metadataUploadForm .addMetadata",function(b){
  b.preventDefault();
  $("#metadataUploadForm tr").last().after("<tr><td><div style='font-size:12px;'><input type='text' name='fields[]' placeholder='metadata'></input></div></td><td><div style='font-size:8px;'><button class='addMetadata'>Add</button></div></td></tr>");
});

$("body").on("click","#metadataUploadForm #metadataSubmit",function(b){
  b.preventDefault();
  const metadata = $('#metadataUploadForm input[type="text"]').map(function() {
    return this.value;
  }).get();

  if(confirm("Please confirm that these fields are what you want \n\n\n" + metadata + "\n\n\n THERE IS NO GOING BACK!!!!")) {
    $.ajax({
      url : $("#metadataUploadForm").attr('action'),
      type: $("#metadataUploadForm").attr('method'),
      data: $("#metadataUploadForm").serializeArray(),
      success: function (textStatus, status) {
        const newFormElText = 
        "<form action='/community-voices/api/images/new/batch' method='post' enctype='multipart/form-data' id='batchUploadForm' style='font-size:0px;'> \
            <input class='custom-file-input' id='csvFile' type='file' name='file' accept='.csv' style='display: none;'/> \
            <input type='button' class='btn btn-outline-primary mr-2' value='Batch Upload' id='fileUploadButton' style='font-size:1rem;'></input>\
        </form>";
        const elz = document.getElementById("metadataUploadForm");
        const formSibling = elz.previousElementSibling;
        elz.remove();
        formSibling.insertAdjacentHTML('afterend',newFormElText);
      },
      error: function(xhr, textStatus, error) {
        alert("Something went wrong. Please contact dashboard@oberlin.edu")
      }
    });
  }
});


