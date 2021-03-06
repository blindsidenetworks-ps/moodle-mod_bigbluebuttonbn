YUI.add('moodle-mod_bigbluebuttonbn-custom', function (Y, NAME) {

M.mod_bigbluebuttonbn = M.mod_bigbluebuttonbn || {};
M.mod_bigbluebuttonbn.custom = {
    bigbluebuttonbn: {},
    lastIndex: 0,

    init: function (bigbluebuttonbn) {
        this.bigbluebuttonbn = bigbluebuttonbn;
        let mainParent = Y.one("#admin-bigbluebuttonbn_enable_cluster");
        let ele = document.getElementById('id_s__bigbluebuttonbn_enable_cluster');
        Y.DOM.setAttribute(ele, 'onchange', 'M.mod_bigbluebuttonbn.custom.showCluster(this);');
        mainParent.insert(this.table, 'after');
        this.showCluster(ele);
        let addServer = document.getElementById("add_server");
        Y.DOM.setAttribute(
            addServer,
            "onclick",
            "M.mod_bigbluebuttonbn.custom.addServer()"
        );

        let servers = JSON.parse(this.bigbluebuttonbn.cluster);
        if (servers) {
            console.log(servers)
            let that = this;
            Object.keys(servers).forEach(function(server, index) {
                let serverName = server;
                let serverUrl = servers[server].server_url;
                let serverSharedSecret = servers[server].shared_secret;
                that.addServer(index, serverName, serverUrl, serverSharedSecret);
            });
        }

        // default row
        this.addServer();
    },

    table: `<div class="container mb-5" id="cluster_table">
                            <div class="row clearfix">
                                <div class="col-md-12 table-responsive">
                                    <table class="table table-bordered table-hover table-sortable">
                                        <thead>
                                            <tr >
                                                <th class="text-center">Server Name</th>
                                                <th class="text-center">Server Url</th>
                                                <th class="text-center">Shared Secret</th>
                                                <th class="text-center" style="border-top: 1px solid #ffffff; border-right: 1px solid #ffffff;"></th>
                                            </tr>
                                        </thead>
                                        <tbody id="cluster-tbody">
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <a id="add_server" class="btn btn-primary text-white float-right" style="cursor: pointer;">Add Server</a>
                        </div>`,

    showCluster: function (ele) {
        ele.checked ? this.enableCluster() : this.disableCluster();
    },

    enableCluster: function () {
        Y.one('#admin-bigbluebuttonbn_server_url').hide();
        Y.one('#admin-bigbluebuttonbn_shared_secret').hide();
        Y.one('#cluster_table').show();
    },

    disableCluster: function () {
        Y.one('#admin-bigbluebuttonbn_server_url').show();
        Y.one('#admin-bigbluebuttonbn_shared_secret').show();
        Y.one('#cluster_table').hide();
    },

    addServer: function (index = this.lastIndex, serverName = '', serverUrl = '', serverSharedSecret = '') {
        let tbody = document.getElementById("cluster-tbody");
        let myTd, myInput, myspan;
        let myTr = document.createElement("tr");
        let placehoilder = ["Server Name", "Server Ulr", "Shared Secret"];
        let nameInput = [
            "bigbluebuttonbn_cluster[" + index + "][server_name]",
            "bigbluebuttonbn_cluster[" + index + "][server_url]",
            "bigbluebuttonbn_cluster[" + index + "][shared_secret]"
        ];

        for (let i = 0; i < nameInput.length; i++) {
            myTd = document.createElement("td");
            myInput = document.createElement("input");
            myInput.setAttribute("type", "text");
            myInput.setAttribute("value", i === 0 ? serverName : i === 1 ? serverUrl : i === 2 ? serverSharedSecret : '');
            myInput.setAttribute("placeholder", placehoilder[i]);
            myInput.setAttribute("name", nameInput[i]);
            myInput.setAttribute("class", "form-control");
            if(i === 0){
                myInput.setAttribute("onkeypress", "return M.mod_bigbluebuttonbn.custom.validationAN(event);");
            }
            myTd.appendChild(myInput);
            myTd.appendChild(myInput);
            myTr.appendChild(myTd);
            if (i === 2) {
                myTd = document.createElement("td");
                myspan = document.createElement("span");
                myspan.textContent = "×";
                let mybutton = document.createElement("button");
                mybutton.setAttribute("class", "btn btn-danger row-remove");
                mybutton.setAttribute("value","delete");
                mybutton.setAttribute("onclick","M.mod_bigbluebuttonbn.custom.deleteRow(this)");
                mybutton.setAttribute("type", "button");
                mybutton.appendChild(myspan);
                myTd.appendChild(mybutton);
                myTr.appendChild(myTd);
                myTr.appendChild(myTd);
            }
        }

        tbody.appendChild(myTr);
        this.lastIndex = index + 1;
    },

    deleteRow: function (rowIdDelete) {
        let row = rowIdDelete.parentNode.parentNode;
        row.parentNode.removeChild(row);
    },

    validationAN: function (e) {
        var regex = new RegExp("^[a-zA-Z0-9]+$");
        var key = String.fromCharCode(
            !e.charCode ? e.which : e.charCode
        );
        if (!regex.test(key)) {
            e.preventDefault();
            return false;
        }
    },
};

}, '@VERSION@', {"requires": ["base", "node"]});
