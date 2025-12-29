function mo_ldap_upgrade() {
	// Redirect to the upgrade tab
	window.location.href = 'index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=licensing';
}

function mo_ldap_premium_attribute_mapping(){
	jQuery('a[href="#premium_features"]').click();
	mo_ldap_add_css_tab("#mo_ldap_premium_features");
}
function mo_ldap_premium_group_mapping(){
	jQuery('a[href="#rolemapping"]').click();
	mo_ldap_add_css_tab("#mo_ldap_group_mapping");
}

function mo_ldap_attribute_mapping_details() 
{
	var attribute_mapping_details=document.getElementById("mo_ldap_attribute_mapping_details");
	if (attribute_mapping_details.style.display==="none") {
    	attribute_mapping_details.style.display="block";
  	} else {
    	attribute_mapping_details.style.display="none";
 	}
}

function mo_ldap_add_css_tab(element) {
	jQuery(".mo_nav_tab_active ").removeClass("mo_nav_tab_active").removeClass("active");
	jQuery(element).addClass("mo_nav_tab_active");
	
	// Check if the logger tab is being activated
	if (element === "#loggers" || jQuery(element).attr('href') === "#loggers") {
		mo_ldap_refresh_logger_tab();
	}
}

function mo_ldap_account_exist(){
	jQuery('#ldap_account_already_exist').submit();
}
	
function mo_ldap_back_to_registration(){
	jQuery('#ldap_cancel_form').submit();
}
	
function mo_ldap_back_btn(){
	jQuery('#mo_otp_cancel_form').submit();
}
		
function mo_ldap_resend_otp_over_email(){
	jQuery('#resend_otp_form').submit();
}

function mo_ldap_test_configuration(){
	var username= jQuery("#test_username_attr").val();
	if(username){
		var url='<?php echo Uri::root(); ?>';
		testconfigurl='index.php?option=com_miniorange_dirsync&view=accountsetup&task=accountsetup.moLdapTestAttributeMapping&test_username_attr='+username;
        var myWindow=window.open(testconfigurl, 'TEST LDAP ATTRIBUTE MAPPING', 'scrollbars=1 width=800, height=800');
		var timer = setInterval(function() {   
            if(myWindow.closed) {  
                clearInterval(timer);  
                location.reload();
            }  
            }, 1); 
	}else{
		alert("Please enter username to see what attributes are retrieved by entered username");
	}
}
		
function mo_ldap_show_proxy_form() {
	jQuery('#submit_proxy1').show();
	jQuery('#register_with_miniorange').hide();
	jQuery('#proxy_setup1').hide();
}
		
function mo_ldap_hide_proxy_form() {
	jQuery('#submit_proxy1').hide();
	jQuery('#register_with_miniorange').show();
	jQuery('#proxy_setup1').show();
	jQuery('#submit_proxy2').hide();
	jQuery('#mo_ldap_registered_page').show();
}
		
function mo_ldap_show_proxy_form2() {
	jQuery('#submit_proxy2').show();
	jQuery('#mo_ldap_registered_page').hide();
}

function mo_ldap_troubleshooting_search_filter() 
{
	var search_filter_details=document.getElementById("mo_ldap_troubleshooting_search_filter");
 	if (search_filter_details.style.display==="none") {
    	search_filter_details.style.display="block";
  	} else {
    	search_filter_details.style.display="none";
 	}
}

function mo_ldap_test_attribute_configuration(){
	var username=jQuery("#test_attribute_username").val();
	var password=jQuery("#test_attribute_password").val();

	if(username){	
		testconfigurl='index.php?option=com_miniorange_dirsync&view=accountsetup&task=accountsetup.attributemappingresults&test_attribute_username='+username+'&test_attribute_password='+password;
        var myWindow=window.open(testconfigurl, 'TEST LDAP ATTRIBUTE MAPPING', 'scrollbars=1 width=800, height=1000');
		
	}else{
		alert("Please enter username to see what attributes are retrieved by entered username");
	}
}
function mo_ldap_submit_search_base(){			
	jQuery('#updatesearchbase_form').submit();				  
		   window.onunload=function(){
			window.opener.location.reload();
			window.close();					
		};
}

function mo_ldap_restart_tourattr() {					  
	tourattr.restart();
}
function mo_ldap_restart_tourrole() {						  
	tourrole.restart();
}

function mo_ldap_ping_ldap_server() {
	var ldapServerUrl=document.getElementById("mo_ldap_server_url").value;

	if (!ldapServerUrl || ldapServerUrl.trim()=="") {
		 alert("Enter LDAP Server URL");
	} else {	
		document.getElementById("ldap_configuration_action").value="ping_ldap_server";
		var form=document.getElementById("mo_ldap_config_form");
		form.submit();
	}
}

function openTab(evt, moAddontabName) {
    var i, addon_tab_content, addon_tab_btn;
    addon_tab_content = document.getElementsByClassName("addon_tab_content");
    for (i = 0; i < addon_tab_content.length; i++) {
        addon_tab_content[i].style.display = "none";
    }
    addon_tab_btn = document.getElementsByClassName("mo_addon_tab_btn");
    for (i = 0; i < addon_tab_btn.length; i++) {
        addon_tab_btn[i].className = addon_tab_btn[i].className.replace(" active", "");
    }
    document.getElementById(moAddontabName).style.display = "block";
    evt.currentTarget.className += " active";
}


function createBaseDiv(base, index) {
    const div = document.createElement('div');
    ['inputGroup', 'list-group-item', 'border', 'rounded', 'p-2', 'mb-2'].forEach(cls => div.classList.add(cls));

    const radioInput = document.createElement('input');
    radioInput.type = 'radio';
    radioInput.name = 'select_ldap_search_bases';
    radioInput.id = `select_ldap_search_${index}`;
    radioInput.classList.add('form-check-input');
    radioInput.value = base;
    radioInput.required = true;

    const label = document.createElement('label');
    label.htmlFor = `select_ldap_search_${index}`;
    label.classList.add('form-check-label', 'ms-2');
    label.innerText = base;

    div.appendChild(radioInput);
    div.appendChild(label);
    return div;
}

function updateSearchBaseLimit() {
    let allBases = document.getElementById('search_base_list_id').value;
    allBases = JSON.parse(allBases);
    const limit = document.getElementById('limit').value;
    const searchBaseResults = document.getElementById('search_base_results');

    searchBaseResults.innerHTML = '';

    const fragment = document.createDocumentFragment();
    allBases.slice(0, limit).forEach((base, i) => {
        fragment.appendChild(createBaseDiv(base, i));
    });
    searchBaseResults.appendChild(fragment);
}

function filterSearchBases() {
    let allBases = document.getElementById('search_base_list_id').value;
    allBases = JSON.parse(allBases);
    const filterx = document.getElementById('search').value.toLowerCase();
    const searchBaseResults = document.getElementById('search_base_results');

    if (filterx === '') {
        updateSearchBaseLimit();
        return;
    }

    const filteredBases = allBases.filter(base => base.toLowerCase().includes(filterx));
    searchBaseResults.innerHTML = '';

    if (filteredBases.length > 0) {
        const fragment = document.createDocumentFragment();
        filteredBases.forEach((base, i) => {
            fragment.appendChild(createBaseDiv(base, i));
        });
        searchBaseResults.appendChild(fragment);
    } else {
        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-warning';
        alertDiv.role = 'alert';
        alertDiv.innerHTML = '<span class="icon-info-circle" aria-hidden="true"></span> No Matching Results';
        searchBaseResults.appendChild(alertDiv);
    }
}


function updateLoggerLimit() {
    const allLogs = JSON.parse(document.getElementById('logger_list_data').value);
    const limit = document.getElementById('limit').value;
    const loggerResults = document.querySelector("#logger_results tbody");
    loggerResults.innerHTML = '';

    // If "All" is selected, show all logs
    const logsToShow = (limit === 'all') ? allLogs : allLogs.slice(0, parseInt(limit, 10));

    if (logsToShow.length > 0) {
        logsToShow.forEach(log => {
            const logData = typeof log.message === 'string' ? JSON.parse(log.message) : log.message;
            const logCode = logData.code || '-';
            const logIssue = logData.issue || '-';
            const logLevel = log.log_level.toLowerCase();
            const badgeClass = logLevel === 'info' ? 'badge bg-success' :
                logLevel === 'warn' ? 'badge bg-warning text-dark' :
                    (logLevel === 'err' || logLevel === 'error') ? 'badge bg-danger' :
                        'badge bg-secondary';

            const iconClass = logLevel === 'info' ? 'fas fa-info-circle text-success' :
                logLevel === 'warn' ? 'fas fa-exclamation-triangle text-warning' :
                    (logLevel === 'err' || logLevel === 'error') ? 'fas fa-times-circle text-danger' :
                        'fas fa-circle text-secondary';

            const timestamp = new Date(log.timestamp);
            const dateStr = timestamp.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
            const timeStr = timestamp.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });

            const row = `
                <tr>
                    <td class="text-center align-middle">
                        <div class="d-flex flex-column align-items-center">
                            <i class="far fa-clock text-muted mb-1"></i>
                            <small class="text-muted">${dateStr}</small>
                            <small class="text-muted">${timeStr}</small>
                        </div>
                    </td>
                    <td class="text-center align-middle">
                        <span class="${badgeClass} fs-6">
                            <i class="${iconClass} me-1"></i>
                            ${log.log_level.toUpperCase()}
                        </span>
                    </td>
                    <td class="text-center align-middle">
                        <code class="bg-light px-2 py-1 rounded text-primary fw-bold">
                            ${logCode}
                        </code>
                    </td>
                    <td class="align-middle">
                        <div class="d-flex align-items-start">
                            <i class="fas fa-chevron-right text-muted me-2 mt-1"></i>
                            <div class="flex-grow-1">
                                ${logIssue.replace(/\n/g, '<br>')}
                            </div>
                        </div>
                    </td>
                </tr>
            `;
            loggerResults.insertAdjacentHTML('beforeend', row);
        });
    } else {
        loggerResults.innerHTML = `
            <tr>
                <td colspan="4" class="text-center py-5">
                    <div class="text-muted">
                        <i class="fas fa-inbox fa-3x mb-3"></i>
                        <h6>No logs available.</h6>
                        <p class="small">No activity logs found. Logs will appear here once authentication events occur.</p>
                    </div>
                </td>
            </tr>
        `;
    }
}

function filterLoggerEntries() {
    const allLogs = JSON.parse(document.getElementById('logger_list_data').value);
    const filterValue = document.getElementById('search').value.toLowerCase().trim();
    const loggerResults = document.querySelector("#logger_results");

    // If search is empty, reset to paginated logs
    if (filterValue === '') {
        updateLoggerLimit();
        return;
    }

    // Clear existing rows
    loggerResults.innerHTML = '';

    // Filter logs based on search term
    const filteredLogs = allLogs.filter(log => {
        // Parse log message (it is JSON)
        const logData = typeof log.message === 'string' ? JSON.parse(log.message) : log.message;
        const logMessage = (logData.issue || '').toLowerCase();
        const logCode = (logData.code || '').toLowerCase();
        const logLevel = log.log_level.toLowerCase();
        const logTimestamp = new Date(log.timestamp).toLocaleString().toLowerCase();

        return (
            logMessage.includes(filterValue) ||
            logCode.includes(filterValue) ||
            logLevel.includes(filterValue) ||
            logTimestamp.includes(filterValue)
        );
    });

    // Populate filtered logs
    if (filteredLogs.length > 0) {
        filteredLogs.forEach(log => {
            const logData = typeof log.message === 'string' ? JSON.parse(log.message) : log.message;
            const logCode = logData.code || '-';
            const logIssue = logData.issue || '-';
            const logLevel = log.log_level.toLowerCase();
            const badgeClass = logLevel === 'info' ? 'badge bg-success' :
                logLevel === 'warn' ? 'badge bg-warning text-dark' :
                    (logLevel === 'err' || logLevel === 'error') ? 'badge bg-danger' :
                        'badge bg-secondary';
            
            const iconClass = logLevel === 'info' ? 'fas fa-info-circle text-success' :
                logLevel === 'warn' ? 'fas fa-exclamation-triangle text-warning' :
                    (logLevel === 'err' || logLevel === 'error') ? 'fas fa-times-circle text-danger' :
                        'fas fa-circle text-secondary';

            const timestamp = new Date(log.timestamp);
            const dateStr = timestamp.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
            const timeStr = timestamp.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });

            const row = `
                <tr>
                    <td class="text-center align-middle">
                        <div class="d-flex flex-column align-items-center">
                            <i class="far fa-clock text-muted mb-1"></i>
                            <small class="text-muted">${dateStr}</small>
                            <small class="text-muted">${timeStr}</small>
                        </div>
                    </td>
                    <td class="text-center align-middle">
                        <span class="${badgeClass} fs-6">
                            <i class="${iconClass} me-1"></i>
                            ${log.log_level.toUpperCase()}
                        </span>
                    </td>
                    <td class="text-center align-middle">
                        <code class="bg-light px-2 py-1 rounded text-primary fw-bold">
                            ${logCode}
                        </code>
                    </td>
                    <td class="align-middle">
                        <div class="d-flex align-items-start">
                            <i class="fas fa-chevron-right text-muted me-2 mt-1"></i>
                            <div class="flex-grow-1">
                                ${logIssue.replace(/\n/g, '<br>')}
                            </div>
                        </div>
                    </td>
                </tr>
            `;
            loggerResults.insertAdjacentHTML('beforeend', row);
        });
    } else {
        loggerResults.innerHTML = `
            <tr>
                <td colspan="4" class="text-center py-5">
                    <div class="text-muted">
                        <i class="fas fa-search fa-3x mb-3"></i>
                        <h6>No matching logs found.</h6>
                        <p class="small">Try adjusting your search criteria.</p>
                    </div>
                </td>
            </tr>
        `;
    }
}


function createLoggerRow(log, index) {
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td>${new Date(log.timestamp).toLocaleString()}</td>
        <td><span class="badge ${getBadgeClass(log.log_level)}">${log.log_level}</span></td>
        <td class="fw-bold text-info">${log.code || '-'}</td>
        <td>${log.message || '-'}</td>
    `;
    return tr;
}

function getBadgeClass(logLevel) {
    const level = logLevel.toLowerCase();
    switch (level) {
        case 'info': return 'bg-success';
        case 'warn': return 'bg-warning text-dark';
        case 'err':
        case 'error': return 'bg-danger';
        default: return 'bg-secondary';
    }
}

function moContactUs(){
	var checkbox = document.getElementById('mo_ldap_query_withconfig');
	var searchFilter = '<?php echo $search_filter; ?>';
	if (checkbox.checked && searchFilter ==""){
		alert('Kindly completed the plugin configuration before sending the configuration along with the query');
		return;
	}
	jQuery('#mo_ldap_contact_us').submit();
}

document.addEventListener('DOMContentLoaded',function(){
	var test = document.querySelectorAll('.mo_ldap_faq_page');
				test.forEach(function(header) {
					header.addEventListener('click', function() {
						var body = this.nextElementSibling;
						body.style.display = body.style.display === 'none' || body.style.display =="" ? 'block' : 'none';
					});
				});
		});

jQuery(document).change(function(){

	var ldapType = document.getElementById("mo_ldap_type").value;
	if(ldapType == 'ldaps')
		document.getElementById('mo_ldap_port').value = '636';

});

function mo_ldap_possible_search_bases(){

	var ldapServerUrl = document.getElementById("mo_ldap_server_url").value;
	if (!ldapServerUrl || ldapServerUrl.trim() == "") {
		alert("Enter LDAP Server URL");
	}
	else{
		testconfigurl = 'index.php?option=com_miniorange_dirsync&view=accountsetup&task=accountsetup.moLdappsbsearchbases';
		var myWindow = window.open(testconfigurl, 'POSSIBLE SEARCH BASES / BASE DNs ', 'scrollbars=1 width=800, height=800');
	}
}

function togglePassword(inputId) {
    var input = document.getElementById(inputId);
    var button = input.nextElementSibling;
    var icon = button.querySelector('i');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    }
}

function checkLdapAttributes() {
    var username = document.getElementById('test_username').value;
    var password = document.getElementById('test_password').value;
    
    if (!username || !password) {
        alert(Joomla.JText._('Password Required'));
        return false;
    }
    
    var testconfigurl = 'index.php?option=com_miniorange_dirsync&view=accountsetup&task=accountsetup.moLdapTestAttributeMapping&test_attribute_username=' + encodeURIComponent(username) + '&test_attribute_password=' + encodeURIComponent(password);
    var myWindow = window.open(testconfigurl, 'TEST LDAP ATTRIBUTE MAPPING', 'scrollbars=1 width=800, height=800');
    var timer = setInterval(function() {
        if(myWindow.closed) {
            clearInterval(timer);
            location.reload();
        }
    }, 1);
}

function updateFileName(input) {
    const fileStatus = document.getElementById('file_status');
    const importBtn = document.getElementById('import_btn');

    if (input.files && input.files[0]) {
        fileStatus.textContent = input.files[0].name;
        importBtn.disabled = false;
    } else {
        fileStatus.textContent = 'No file uploaded';
        importBtn.disabled = true;
    }
}
/**
 * Displays the log details in a Bootstrap modal.
 *
 * @param {string} level - The log level (e.g., "Error", "Warning", "Info").
 * @param {string} date - The timestamp of the log entry.
 * @param {string} message - The log message content.
 * @param {string} file - The file where the log was generated.
 * @param {string} functionName - The function in which the log was triggered.
 * @param {string} line - The line number where the log occurred.
 */
function showLogModal(level, date, message, file, functionName, line) {
    document.getElementById("logType").innerText = level;
    document.getElementById("logDate").innerText = date;
    document.getElementById("logMessage").innerText = message;
    document.getElementById("logFile").innerText = file;
    document.getElementById("logFunction").innerText = functionName;
    document.getElementById("logLine").innerText = line;

    var logModal = new bootstrap.Modal(document.getElementById('logModal'));
    logModal.show();
}


document.addEventListener("DOMContentLoaded", function () {
    // Initialize logger functionality if the page contains logger elements
    const loggerToggle = document.getElementById("mo_ldap_logger_toggle");
    if (loggerToggle) {
        // Add visual feedback when toggle is clicked
        loggerToggle.addEventListener("change", function () {
            const form = this.closest('form');
            if (form) {
                // Add loading state
                const label = this.nextElementSibling;
                const originalText = label.textContent;
                label.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Updating...';
                
                // Submit the form
                form.submit();
            }
        });
    }
});

// Logger Filter Functions
function mo_ldap_auto_submit_filters() {
    // Auto-submit form when limit changes
    jQuery('#limit').on('change', function() {
        jQuery('#logger-filter-form').submit();
    });
    
    // Auto-submit form when level filter changes
    jQuery('#level').on('change', function() {
        jQuery('#logger-filter-form').submit();
    });
}

function mo_ldap_clear_logger_filters() {
    // Clear all filter inputs
    jQuery('#search').val('');
    jQuery('#level').val('');
    jQuery('#date_from').val('');
    jQuery('#date_to').val('');
    jQuery('#code').val('');
    jQuery('#limit').val('25');
    
    // Redirect to clean logger URL
    window.location.href = 'index.php?option=com_miniorange_dirsync&view=accountsetup&tab-panel=moLoggers';
}

function mo_ldap_validate_date_range() {
    var dateFrom = jQuery('#date_from').val();
    var dateTo = jQuery('#date_to').val();
    
    if (dateFrom && dateTo && dateFrom > dateTo) {
        alert('Logger date range is invalid.');
        return false;
    }
    return true;
}

// Initialize logger filters when document is ready
jQuery(document).ready(function($) {
    // Auto-submit for dropdown changes
    mo_ldap_auto_submit_filters();
    
    // Validate date range before form submission
    $('#logger-filter-form').on('submit', function(e) {
        if (!mo_ldap_validate_date_range()) {
            e.preventDefault();
            return false;
        }
    });
    
    // Add keyboard shortcut for search (Ctrl+F)
    $(document).on('keydown', function(e) {
        if (e.ctrlKey && e.key === 'f') {
            e.preventDefault();
            $('#search').focus();
        }
    });
    
    // Add clear filters button functionality
    $('.btn-clear-filters').on('click', function(e) {
        e.preventDefault();
        mo_ldap_clear_logger_filters();
    });
});

//Toggle to view export configuration page
function toggleImportExportView() {
    var configContent = document.getElementById('ldapConfigurationContent');
    var importExportView = document.getElementById('importExportView');

    if (configContent && importExportView) {
        if (configContent.style.display === 'none') {
            configContent.style.display = 'block';
            importExportView.style.display = 'none';
        } else {
            configContent.style.display = 'none';
            importExportView.style.display = 'block';
        }
    }
}