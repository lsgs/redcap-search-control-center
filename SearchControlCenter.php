<?php
/**
 * REDCap External Module: SearchControlCenter
 * REDCap external module that enables searching for and navigating to Control Center settings.
 * @author Luke Stevens, Murdoch Children's Research Institute
 */
namespace MCRI\SearchControlCenter;

use ExternalModules\AbstractExternalModule;

class SearchControlCenter extends AbstractExternalModule
{
    protected static $CaptureFromNodes = array('div','p','span','a','b','i','em','u','strong','h1','h2','h3','h4','h5','h6','code','ul','ol','li','label','td','th','pre');
    /** @var int Capture result success = 1 */
	const sccCaptureResultSuccess = 1;
    /** @var int Capture result ignore = 0 */
	const sccCaptureResultIgnore = 0;
    /** @var int Capture result failed = -1 */
	const sccCaptureResultFailed = -1;

    public function redcap_control_center(): void {
        if (!defined('SUPER_USER') || !SUPER_USER) return;

        $captureDetails = $this->captureDetails();
        $cccVer = $captureDetails['version'];
        if (empty($cccVer)) {
            $alert = '<i class="fa-solid fa-triangle-exclamation text-danger ml-3" data-bs-toggle="tooltip" data-bs-placement="right" data-bs-title="Content capture not yet performed! Go to the External Modules &quot;Control Center Content Capture&quot; page."></i>';
            $inputDisabled = 'disabled="disabled"';
        } else if (defined('REDCAP_VERSION') && $cccVer!==REDCAP_VERSION) {
            $alert = '<i class="fa-solid fa-triangle-exclamation text-warning ml-3" data-bs-toggle="tooltip" data-bs-placement="right" data-bs-title="Content refresh recommended. Go to the External Modules &quot;Control Center Content Capture&quot; page."></i>';
            $inputDisabled = "";
        } else {
            $alert = '';
            $inputDisabled = "";
        }
        $this->initializeJavascriptModuleObject();
        $now = str_replace(['-',' ',':'],'',NOW);
        ?>
        <style type="text/css">
            #em-search-control-center-input { width: 100%; }
        </style>
        <script type="text/javascript">
            /* External Module: Search Control Center */
            $(function(){
                let module = <?=$this->getJavascriptModuleObjectName()?>;

                module.search = function(request, response) {
                    module.ajax('search', request.term).then(function(data) {
                        response(data);
                    }).catch(function(err) {
                        console.log(err);
                        return null;
                    });
                };

                module.responseHandler = function(data) {
                    return data;
                };
        
                module.init = function() {
                    $('#em-search-control-center-menu-item').appendTo($('div.cc_menu_section').eq(1)).show();
                    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
                    const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));

                    $("#em-search-control-center-input").autocomplete({
                        source: module.search,
                        delay: 500,
                        minLength: 5,
                        select: function (event, ui) {
                            event.preventDefault();
                            window.location.href = ui.item.result_url;
                        },
                        focus: function (event, ui) {
                            event.preventDefault();
                            $(this).val('');
                        }
                    })
                    .data('ui-autocomplete')._renderItem = function( ul, item ) {
                        if (!item.hasOwnProperty('result_url') || !item.hasOwnProperty('result_display')) return false;
                        return $("<li></li>")
                            .data("url", item.result_url)
                            .append("<a>"+item.result_display+"</a>")
                            .appendTo(ul);
                    };
                };
                $(document).ready(function(){
                    module.init();
                });
            });
        </script>
        <div id="em-search-control-center-menu-item" class="cc_menu_item" style="display:none;"><label for="em-search-control-center-input" style="display:inline;"><i class="fas fa-magnifying-glass"></i> <span>Search Control Center Settings</span> <?=$alert?></label><br><input id="em-search-control-center-input" type="text" class="x-form-text x-form-field fs11" autocomplete="em-serarch-control-center-<?=$now?>" <?=$inputDisabled?> placeholder="Enter search term"/></div>
        <?php 
    }

    public function redcap_module_link_check_display($project_id, $link) {
        if (!defined('SUPER_USER') || !SUPER_USER) {
            return null;
        }
        return $link;
    }

    public function redcap_module_ajax($action, $payload, $project_id, $record, $instrument, $event_id, $repeat_instance, $survey_hash, $response_id, $survey_queue_hash, $page, $page_full, $user_id, $group_id) {
        switch ($action) {
            case 'capture-details': $return = $this->captureDetails(); break;
            case 'capture-content': $return = $this->captureContent($payload); break;
            case 'capture-complete': $return = $this->captureComplete(); break;
            case 'search': $return = $this->search($payload); break;
            default: $return = null; break;
        }
        return $return;
    }

    public function manageContentCapturePage(): void {
        $this->initializeJavascriptModuleObject();

        $captureDetails = $this->captureDetails();
        $cccVer = $captureDetails['version'];
        $cccTime = $captureDetails['time'];
        $cccTime = (empty($cccTime)) ? ' - ' : $this->escape($cccTime);

        if (empty($cccVer)) {
            $badgeClass = 'badge-danger fs14';
            $cccVer = ' - ';
        } else {
            $badgeClass = 'badge-success fs14';
            $this->escape($cccVer);
        }
        ?>
        <h4 style="margin-top:0;" class="clearfix"><div class="pull-left float-left"><i class="fas fa-wrench mr-1"></i>Manage Control Center Content Capture</div></h4>
        <div>
            <p>Last capture of Search Control Center content:<ul><li class="mb-1">REDCap Version: <span id="em-search-control-center-version" class="badge <?=$badgeClass?>"><?=$cccVer?></span></li><li>Date/time: <span id="em-search-control-center-time" class="badge <?=$badgeClass?>"><?=$cccTime?></span></li></ul></p>
            <p><button id="em-search-control-center-capture" class="btn btn-med btn-primaryrc" type="button"><i class="fa-solid fa-arrows-rotate"></i> Refresh Content Capture</button></p>
        </div>
        <div id="em-search-control-center-capture-results">
        </div>
        <style type="text/css">
        </style>
        <script type="text/javascript">
            /* External Module: Search Control Center */
            $(function(){
                let module = <?=$this->getJavascriptModuleObjectName()?>;
                module.sccCaptureResultSuccess = <?=self::sccCaptureResultSuccess?>;
                module.sccCaptureResultIgnore = <?=self::sccCaptureResultIgnore?>;
                module.sccCaptureResultFailed = <?=self::sccCaptureResultFailed?>;
                module.cc_refreshContent = function() {
                    $('#em-search-control-center-capture-results').html('');
                    $('#em-search-control-center-capture').prop('disabled', true);
                    const resultContainer = '<div id="em-search-control-center-capture-result-|ID|">|SECTION|: |TEXT| <div class="spinner-border spinner-border-sm mx-2" role="status"><span class="visually-hidden">Loading...</span></div></div>';
                    let id = 0;
                    $('#control_center_menu').find('a:visible').each(function() {
                        id++;
                        let thisText = $(this).text();
                        if (thisText.startsWith('To-Do List')) thisText = 'To-Do List';
                        let thisSection = $(this).closest('.cc_menu_section').find('.cc_menu_header:first').text();
                        if (thisSection.startsWith('External Modules')) thisSection = 'External Modules';
                        let thisHref = $(this).attr('href');
                        let thisResult = resultContainer.replace('|ID|', id).replace('|SECTION|', thisSection).replace('|TEXT|', thisText).replace('|HREF|', thisHref);
                        $('#em-search-control-center-capture-results').append(thisResult);
                        module.ajax('capture-content', [id,thisSection,thisText,thisHref]).then(function(response) {
                            let result = '';
                            switch (response.result) {
                                case module.sccCaptureResultSuccess:
                                    result = '<span class="text-success"><i class="fas fa-check-circle"></i> Capture complete</span>';
                                    break;
                                case module.sccCaptureResultIgnore:
                                    result = '<span class="text-muted"><i class="fas fa-minus-circle"></i> Capture ignored - external site</span>';
                                    break;
                                default:
                                    result = '<span class="text-danger"><i class="fas fa-times-circle"></i> '+woops+'</span>';
                                    break;
                            }
                            module.updateResult(response.id, result);
                            if (response.id==id) {
                                module.ajax('capture-complete', []).then(function(response) {
                                    ['version','time'].forEach(function(prop) {
                                        if (response.hasOwnProperty(prop)) {
                                            $('#em-search-control-center-'+prop).removeClass('badge-danger').addClass('badge-success').text(response[prop]);
                                        }
                                    });
                                }).catch(function(err) {
                                    $('#em-search-control-center-capture-results').append('<span class="text-danger">'+err+'</span>');
                                });
                            }
                        }).catch(function(err) {
                            $('#em-search-control-center-capture-results').append('<span class="text-danger">'+err+'</span>');
                        });
                    });
                };
                module.updateResult = function(id, content) {
                    $('#em-search-control-center-capture-result-'+id).find('.spinner-border').replaceWith(content);
                };
                module.cc_init = function() {
                    $('#em-search-control-center-capture').on('click', module.cc_refreshContent);
                };
                $(document).ready(function(){
                    module.cc_init();
                });
            });
        </script>
        <?php 
    }

    /**
     * captureDetails()
     * Read the REDCap version and user-preference-formatted date/time of the last content capture
     * @return array ['version'=>'v','time'=>'t']
     */
    protected function captureDetails(): array {
        $cccVer = $this->escape($this->getSystemSetting('content-capture-version'));
        $cccTime = $this->escape($this->getSystemSetting('content-capture-time'));
        
        if (\DateTimeRC::validateDateFormatYMD($cccTime)) {
            $cccTime = \DateTimeRC::format_user_datetime($this->escape($cccTime), 'Y-M-D_24');
        } else {
            $cccTime = '';
        }

        return array(
            'version' => $cccVer,
            'time' => $cccTime
        );
    }

    /**
     * captureHref()
     * Capture the html content for the specified url
     * @param array payload [id,linkSection,linkText,linkHref]
     * @return array [id,result] result: sccCaptureResultSuccess=1; sccCaptureResultIgnore=0; sccCaptureResultFailed=-1
     */
    protected function captureContent($payload): array {
        $result = array('id'=>$payload[0]);
        $section = $payload[1];
        $title = $payload[2];
        $url = $payload[3];
        
        if (starts_with($url,'/') || starts_with($url,APP_PATH_WEBROOT_FULL)) {
            if (starts_with($url,'/')) $url = APP_PATH_WEBROOT_FULL.str_replace(APP_PATH_WEBROOT_PARENT,'',$url);
            $divText = $section.' '.$title;
            $html = $this->http_get($url);
            $page = new \DOMDocument();
            $page->loadHTML($html); 
            $xpath = new \DOMXPath($page);

            $query = "//div[@id='control_center_window']//*[not(self::script)]";
            $nodes = $xpath->query($query);

            if ($nodes->length === 0) {
                $query = "//div[@id='pagecontent']//*[not(self::script)]";
                $nodes = $xpath->query($query);
            }

            if ($nodes->length === 0) {
                $query = "//body//*[not(self::script)]";
                $nodes = $xpath->query($query);
            }

            //$ignoredNodes = array();
            foreach ($nodes as $node) {
                if (in_array($node->nodeName, static::$CaptureFromNodes)) {
                    $nodeText = trim($node->nodeValue);
                    if (preg_match('/\b[a-z][a-z_]+_\d+\s=\s/m', $nodeText)) {
                        $nodeText = ''; // ignore literal text from language file found in some pages
                    } else if (length($nodeText) > 64*1024) {
                        $nodeText = substr($nodeText, 0, 64*1024);
                    }
                    $divText .= (empty($nodeText)) ? '' : '\n'.$nodeText;
                //} else if (array_key_exists($node->nodeName, $ignoredNodes)) {
                //    $ignoredNodes[$node->nodeName]++;
                //} else {
                //    $ignoredNodes[$node->nodeName] = 1;
                }
            }
            //$this->log('Ignored nodes: '.print_r($ignoredNodes, true));
            $result['result'] = $this->updateCapturedContent($section, $title, $url, $divText);
        } else {
            $result['result'] = self::sccCaptureResultIgnore;
        }

        return $result;
    }

    /**
     * captureComplete()
     * Update the REDCap version and user-preference-formatted date/time of the last content capture
     * @return array ['version'=>'v','time'=>'t']
     */
    protected function captureComplete(): array {
        $this->setSystemSetting('content-capture-version', REDCAP_VERSION);
        $this->setSystemSetting('content-capture-time', NOW);
        return $this->captureDetails();
    }

    /**
     * http_get()
     * Copy of curl call from \http_get() but including cookie so can get authenticated page content
     */
    protected function http_get($url="", $timeout=null, $basic_auth_user_pass="", $headers=array(), $user_agent=null) {
        $cookieString = '';
        foreach ($_COOKIE as $key => $value) {
            $cookieString.=\REDCap::escapeHtml($key)."=".\REDCap::escapeHtml($value)."; ";
        }

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_COOKIE, $cookieString);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_AUTOREFERER, true);
        curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_VERBOSE, 0);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HTTPGET, true);
        if (!sameHostUrl($url)) {
            curl_setopt($curl, CURLOPT_PROXY, PROXY_HOSTNAME); // If using a proxy
            curl_setopt($curl, CURLOPT_PROXYUSERPWD, PROXY_USERNAME_PASSWORD); // If using a proxy
        }
        curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1); // Don't use a cached version of the url
        if (is_numeric($timeout)) {
            curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $timeout); // Set timeout time in seconds
        }
        // If using basic authentication = base64_encode(username:password)
        if ($basic_auth_user_pass != "") {
            curl_setopt($curl, CURLOPT_USERPWD, $basic_auth_user_pass);
            // curl_setopt($curl, CURLOPT_HTTPHEADER, array("Authorization: Basic ".$basic_auth_user_pass));
        }
        // If passing headers manually, then add then
        if (!empty($headers) && is_array($headers)) {
            //curl_setopt($curl, CURLOPT_HTTPHEADER, array("Authorization: Bearer ".$authorizationBearerToken));
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        }
        // If passing user agent
        if ($user_agent != null) {
            curl_setopt($curl, CURLOPT_USERAGENT, $user_agent);
        }
        // Execute it
        $response = curl_exec($curl);
        $info = curl_getinfo($curl);
        curl_close($curl);
        // If returns certain HTTP 400 or 500 errors, return false
        if (isset($info['http_code']) && ($info['http_code'] == 404 || $info['http_code'] == 407 || $info['http_code'] >= 500)) return false;
        return $response;
    }

    /**
     * updateCapturedContent()
     * Save captured content to module settings
     * @param string section
     * @param string title
     * @param string url
     * @param string page text
     * @return int result: 1=success; -1=fail
     */
    protected function updateCapturedContent(string $section,string $title,string $url,string $text): int {
        $allSettings = $this->getSystemSettings();

        $found = false;
        if (isset($allSettings['control-center-pages']['system_value']) && count($allSettings['control-center-pages']['system_value']) > 0) {
            foreach (array_keys($allSettings['control-center-pages']['system_value']) as $idx) {
                if ($allSettings['cc-page-section']['system_value'][$idx] == $section && $allSettings['cc-page-title']['system_value'][$idx] == $title) {
                    $thisPageIndex = $idx;
                    break;
                }
            }
            $thisPageIndex = ($found) ? $thisPageIndex : $idx + 1;
        } else {
            $thisPageIndex = 0;
        }
        
        
        $allSettings['control-center-pages']['system_value'][$thisPageIndex] = 'true';
        $allSettings['cc-page-section']['system_value'][$thisPageIndex] = $section;
        $allSettings['cc-page-title']['system_value'][$thisPageIndex] = $title;
        $allSettings['cc-page-url']['system_value'][$thisPageIndex] = $url;
        $allSettings['cc-page-text']['system_value'][$thisPageIndex] = $text;

        try {
            $this->setSystemSetting('control-center-pages', $allSettings['control-center-pages']['system_value']);
            $this->setSystemSetting('cc-page-section', $allSettings['cc-page-section']['system_value']);
            $this->setSystemSetting('cc-page-title', $allSettings['cc-page-title']['system_value']);
            $this->setSystemSetting('cc-page-url', $allSettings['cc-page-url']['system_value']);
            $this->setSystemSetting('cc-page-text', $allSettings['cc-page-text']['system_value']);
            $result = self::sccCaptureResultSuccess;
        } catch (\Throwable $th) {
            $this->log("$section, $title: ".$th->getMessage());
            $result = self::sccCaptureResultFailed;
        }
        return $result;
    }

    /**
     * search()
     * Search for supplied text in control centre page text content
     * @param string search query
     * @return array results: array of arrays containing section/title/url/text fragment
     */
    protected function search(string $query): array {
        $lenPrefix = 50;
        $lenSuffix = 50;
        $results = array();

        $allSettings = $this->getSystemSettings();
        
        foreach ($allSettings['cc-page-text']['system_value'] as $idx => $text) {
            $pos = stripos($text, $query); // case-insensitive
            if ($pos!==false) {
                $matchPrefix = substr($text, ($pos-$lenPrefix < 0) ? 0 : $pos-$lenPrefix, $lenPrefix);
                $matched = substr($text, $pos, strlen($query));
                $matchSuffix = substr($text, $pos+strlen($query), $lenSuffix);

                $displayText = '<strong>'.$allSettings['cc-page-section']['system_value'][$idx].': ';
                $displayText .= $allSettings['cc-page-title']['system_value'][$idx].'</strong><br>';
                $displayText .= '<span>'.$matchPrefix.'<span class="rc-search-highlight">'.$matched.'</span>'.$matchSuffix.'</span>';
                
                $results[] = array(
                    'result_url' => $allSettings['cc-page-url']['system_value'][$idx].'#:~:text='.urlencode($matched),
                    'result_display' => $displayText
                );
            }
        }

        return $results;
    }
}