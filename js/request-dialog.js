/*
 * Modual Dialog UI to present request option to user 
 */

function RequestDialog(params) {
    
    //load parameters
    params = params || {};
    this.isbn = params.isbn || null;
    this.itemTitle = params.itemTitle || "";
    this.cssPath = params.cssPath || "";
    this.container = params.container || document.body;
    this.servicePath = params.servicePath || "";
    
    //define class vars
    this.checker = null;
    this.cssLoaded = false;
    this.display = false;
    this.requestService = null;
    
    //if the stylesheet doesn't exist, pull it in
    if(!$j("style[href*='mira-dailog.css']").length > 0)
    {
        $j("<link>")
            .attr("type","text/css")
            .attr("rel","stylesheet")
            .attr("href",this.cssPath+"mira-dialog.css?v="+Math.random()%1000)
            .appendTo("head")
            .load((function(dialog){
                return function() {
                    dialog.cssLoaded = true;
                    
                    if(dialog.display) {
                        dialog.ShowDialog();   
                    }
                }
            })(this));
    }
    
    /*
     * Create the dialog elements
     */
    //overlay
    $j("<div>")
        .addClass("ira-overlay")
        .appendTo(this.container)
        .hide();
    
    //dialog
    $j("<div>")
        .addClass("ira-dialog")
    
        //header
        .append($j("<h3>")
            .addClass("ira-dialog-header")
            .html("Request:")
            .append($j("<i>").html(this.itemTitle)))
    
        //"X" (close) button
        .append($j("<button>")
            .addClass("ira-close-dialog")
            .html("&times;")
            .attr("title","Close Dialog")
            .attr("type","button")
            .click(this.CloseDialog))
    
        //content
        .append($j("<div>")
            .addClass("ira-dialog-content")
            .append($j("<img>")
                .attr("id", "ira-processing-indicator")
                .attr("src","data:image/gif;base64,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"),
                
                $j("<div>")
                .addClass("ira-status")
                   )
               )
        .appendTo(this.container)
        .hide();
    
    return this;
}

RequestDialog.prototype.Init = function(){
    var reqDialog = this;
    if(typeof(availibilityChecker) != "undefined")
    {
        this.checker = new availibilityChecker({
            localLibName: local.name,
            localSearchUrl: local.searchUrl,
            cssPath: this.servicePath,
            removeDuplicates: true,
            localOnly: false,
            servicePath: this.servicePath
        });
        
        $j(".ira-status").html("Checking item availibility...").show();
        this.checker.retrieveData({
            displayCallback: function(data){
                
                var data = this.data, isbn = this.isbn;
                
                analysisResults = {
                    registeredTags: [],
                    availItemCount: 0,
                    libraryCount: 0
                }
                
                //if the availibity data was successfully retrieved, analyze the results
                if(data.reqStatus == "success") {
                    reqDialog.AnalyzeAvailbilityData(data, analysisResults /*out*/);
                }
                //otherwise, the system will default ILL, so just log error and the user continues without knowing
                else {
                    console.error("Error retrieving availibility data: " + data.reqStatus);
                }
                
                /*
                 * Indicate how the item will be acquired and how long it will take
                 * -------------------------------------------------------------------------------------------------------------
                 */
                
                //update UI
                $j("#ira-processing-indicator").hide();
                $j(".ira-status").hide();
                var requestService = "";
                
                if(data.localAvail)
                {
                    $j(".ira-status")
                        .html("This item is currently available at the " + local.libName + "! The reference librarian (near the circulation desk) can assist you if you need help finding it.")
                        .addClass("success")
                        .addClass("result")
                        .show();
                }
                else {
                    //General request explanation
                    $j("#ira-processing-indicator").before(
                        //Description and Availibility Information
                        $j("<p>").text("While this item won't be available for a while from the " + local.libName + ", other libraries we work with have it available, and we can request it from them:"));


                    var ar = analysisResults;
                    //If the items was found through another millenium system where it can be automatically requested
                    if(ar.availItemCount > 0) {
                        //store request service
                        reqDialog.requestService = ar.registeredTags[0];

                        //sentence structure variables
                        var libraryPlurality = (ar.libraryCount > 1) ? "ies" : "y";
                        var networkPlurality = (ar.registeredTags.length > 1) ? "s" : "";

                        //scope event variables
                        var servicePath = this.servicePath;

                        //Yields: Available from # other (CNY/NEX/...) librar(y|ies), in about X* days. View Full Availibility Information
                        $j("#ira-processing-indicator").before(
                            $j("<p>").html("Available from <b>" + ar.libraryCount + "</b> other " + ar.registeredTags.join("/").toUpperCase() + " librar" + libraryPlurality + ", in about <span class='fulfillment-disclaimer'>" + systems[ar.registeredTags[0]].fulfillment + "</span> days. ")
                                .append(
                                    $j("<br>"),
                                    $j("<a>")
                                    .html("View Full Availibility Information")
                                    .attr("target","_blank")
                                    .attr("href", "javascript:void(0)")
                                    .click(function(){
                                        window.open(servicePath + "itemAvailibility.php?isbn=" + isbn + "&title=" + encodeURI(reqDialog.itemTitle), "Item Availibility: " + reqDialog.itemTitle, "width=600, height= 800, location=no");
                                    })));
                    }
                    //If it wasn't found anywhere, it will be request through Interlibrary Loan
                    else {
                        //store request service
                        reqDialog.requestService = local.fallback;

                        //Yields: Item will be requested through [fallback service] with an estimated fulfillment of X* business days.
                        $j("#ira-processing-indicator").before(
                            $j("<p>").append(
                                "Item will be requested through ",
                                $j("<a>")
                                    .attr("target","_blank")
                                    .attr("href",systems[local.fallback].info_url)
                                    .html(systems[local.fallback].name), 
                                " with an estimated fulfillment of <span class='fulfillment-disclaimer'>" + systems[local.fallback].fulfillment + "<span> business days.")
                        )
                    }

                    /*
                     * Append auth form to ensure a valid user before attempting request, and allowing direct POSTing to services
                     */
                    var showStatus = this.ShowStatus;
                    $j(".ira-dialog-content").append(

                        //Authentication Form
                        $j("<form>")
                            .attr("id","ira-auth-form")
                            .append(
                                $j("<label>")
                                    .html(local.name + " Username")
                                    .attr("for","ira-username"),

                                $j("<input>")
                                    .attr("type","text")
                                    .attr("id","ira-username")
                                    .attr("name","un"),

                                $j("<label>")
                                    .html("Password")
                                    .attr("for","ira-password"),

                                $j("<input>")
                                    .attr("type","password")
                                    .attr("id","ira-password")
                                    .attr("name","pw"),

                                $j("<button>")
                                    .attr("type","button")
                                    .html("Request Item")
                                    .addClass("ira-req-button")
                                    .click($j.proxy(reqDialog.AuthUser, reqDialog))));
                    $j(".ira-dialog-content input").keyup(function(e){
                        if(e.keyCode == 13)
                        {
                            $j(".ira-req-button").click();
                            $j(this).focus();
                        }
                    });
                }
            },
            isbn: this.isbn
        });
    }
    else {
        console.log("Availibility Checker is missing!");
        return false;
    }
}

RequestDialog.prototype.ShowDialog = function() {
    console.log("display dialog");
    
    if(this.cssLoaded)
    {
        $j(".ira-overlay").show();
        $j(".ira-dialog").show();    
    }
    else {
        this.display = true;   
    }
}

RequestDialog.prototype.CloseDialog = function() {
    $j(".ira-overlay").remove();
    $j(".ira-dialog").remove();
    MIRA_DIALOG_OPEN = false;
}

RequestDialog.prototype.ShowStatus = function(status) {
   console.log("display availibility"); 
}

RequestDialog.prototype.AuthUser = function() {
    
    $j(".ira-status").hide();
    $j("#ira-auth-form").hide();
    $j(".ira-req-button").hide();
    $j("#ira-processing-indicator").show();

    var reqId = parseInt(Math.random() * 1000);
    window[reqId] = this;
    console.log(this.isbn);
    $j.getJSON(this.servicePath + "includes/auth.php?callback=?", {reqId: reqId, un: $j("#ira-username").val(), pw: $j("#ira-password").val()}, function(){alert("test");});
}

RequestDialog.prototype.GenerateRequest = function() {
    var reqID = parseInt(Math.random() * 1000);
    window[reqID] = this;
    $j.getJSON(this.servicePath + "includes/generate-request.php?callback=?", {reqId: reqID, isbn: this.isbn, system: this.requestService, user: $j("#ira-username").val(), password: $j("#ira-password").val()});
}

RequestDialog.prototype.AnalyzeAvailbilityData = function(data, ar)
{
    //Analyze records
    for(r in data.records){

        record = data.records[r];

        //If this record is either from the local library or is available (don't bother with external unavailable)
        if(!(record.lib != this.localLib && record.status.toLowerCase() != "available")){

            //Mark as unavailable
            if(record.status.toLowerCase() == "available"){
                ar.availItemCount++;
            }

            //Check if the last record is from the same library, and if so, don't display a library label
            if(!(data.records[r-1] && record.lib == data.records[r-1].lib)){
                ar.libraryCount++;
            }

            //link to the local catalog
            if(record.lib != this.localLib){

                if($j.inArray(record.system, ar.registeredTags) == -1)
                {
                    ar.registeredTags[ar.registeredTags.length] = record.system;
                }
            }
        }
    }
}

function authCallback(data) {
    
    if(data.reqId)
    {
        var reqDialog = window[data.reqId];
        $j('.ira-status').attr("class","ira-status");
        
        switch(data.status)
        {
            case "no credentials":
            case "invalid credentials":
                $j("#ira-processing-indicator").hide();
                $j(".ira-req-button").show();
                $j("#ira-auth-form").show();

                $j(".ira-status").html("Invalid Username and Password Combination")
                    .addClass("error")
                    .show();
                break;
            case "authenticated":
                $j(".ira-status").html("Authenticated! Processing request...").show();

                reqDialog.GenerateRequest();
                break;
            default:
                $j(".ira-status")
                    .html("An unexpected error occured. If this issue persists, please notify us.")
                    .addClass("error")
                    .show();
                break;
        }
    }
}

function requestCallback(data) {
    $j("#ira-processing-indicator").hide();
    $j(".ira-status")
        .attr("class","ira-status")
        .addClass("result");
    var instr = "<p> You can attempt to create the request again through this dialog, or directly request the material from " + data.system
    switch(data.status)
    {
        case "complete":
            
            $j(".ira-status")
                .html("Request Sent via " + data.system + ".")
                .addClass("success")
                .append(
                $j("<p>")
                    .text("You will recieve an email notification when your item has arrived, and can be picked from the Circulation Desk."));
            
            if(data.requestURL)
            {
                $j('.ira-status').append(
                    $j("<p>")
                        .html("Request can be reviewed at: ")
                        .append(
                        $j("<a>")
                            .html(data.requestURL)
                            .attr("target","_blank")
                            .attr("href", data.requestURL)));
            }
            break;
        case "error":
            $j('.ira-status')
                .html("An error has occured creating your request: <p class='ira-server-error'>" + data.error + instr + " <p>If this issue persists please contact the system administrator.")
                .addClass("error");
            break;
        default:
            $j(".ira-status")
                .html("An unexpected error has occured. Please contact the system administrator is this issue persists" + instr)
                .addClass("error");
            break;
    }
}