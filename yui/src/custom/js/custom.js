M.mod_bigbluebuttonbn = M.mod_bigbluebuttonbn || {};
M.mod_bigbluebuttonbn.custom = {
  cluster: function () {
    let mainParent = Y.one("#admin-bigbluebuttonbn_enable_cluster");
    let ele = document.getElementById("id_s__bigbluebuttonbn_enable_cluster");
    Y.DOM.setAttribute(
      ele,
      "onchange",
      "M.mod_bigbluebuttonbn.custom.showCluster(this);"
    );
    mainParent.insert(this.table, "after");
    this.showCluster(ele);

    let addServer = document.getElementById("add_server");
    Y.DOM.setAttribute(
      addServer,
      "onclick",
      "M.mod_bigbluebuttonbn.custom.addServer()"
    );
  },

  table: `<div class="container mb-5" id="cluster_table">
                            <div class="row clearfix">
                                <div class="col-md-12 table-responsive">
                                    <table class="table table-bordered table-hover table-sortable" id="tab_logic">
                                        <thead>
                                            <tr >
                                                <th class="text-center">Server Name</th>
                                                <th class="text-center">Server Url</th>
                                                <th class="text-center">Shared Secret</th>
                                                <th class="text-center" style="border-top: 1px solid #ffffff; border-right: 1px solid #ffffff;"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr data-index="0">
                                                <td data-name="server_name">
                                                    <input type="text" name="server_name"  placeholder="Server Name" class="form-control"/>
                                                </td>
                                                <td data-name="server_url">
                                                    <input type="text" name="server_url" placeholder="Server Url" class="form-control"/>
                                                </td>
                                                <td data-name="shared_secret">
                                                    <input type="text" name="shared_secret" placeholder="Shared Secret" class="form-control"/>
                                                </td>
                                                <td data-name="del">
                                                    <button class='btn btn-danger row-remove' value="Delete" onclick="M.mod_bigbluebuttonbn.custom.deleteRow(this)" type="button"><span>×</span></button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <a id="add_server" class="btn btn-primary text-white float-right">Add Server</a>
                        </div>`,
  
  showCluster: function (ele) {
    ele.checked ? this.enableCluster() : this.disableCluster();
  },

  enableCluster: function () {
    Y.one("#admin-bigbluebuttonbn_server_url").hide();
    Y.one("#admin-bigbluebuttonbn_shared_secret").hide();
    Y.one("#cluster_table").show();
  },

  disableCluster: function () {
    Y.one("#admin-bigbluebuttonbn_server_url").show();
    Y.one("#admin-bigbluebuttonbn_shared_secret").show();
    Y.one("#cluster_table").hide();
  },
  addServer: function () {

    let parentTable = document.getElementById("tab_logic").getElementsByTagName('tbody')[0];
    let myTd, myInput, myspan;
    let myTr = document.createElement("tr");
    let placehoilder = ["Server Name", "Server Ulr", "Shared Secret"];
    let nameInput = ["server_name", "server_url", "shared_secret"];

    for (let i = 0; i < 3; i++) {
      myTd = document.createElement("td");
      myInput = document.createElement("input");
      myInput.setAttribute("type", "text");
      myInput.setAttribute("placeholder", placehoilder[i]);
      myInput.setAttribute("name", nameInput[i]);
      myInput.setAttribute("class", "form-control");
      myTd.setAttribute("data-name",nameInput[i]);
      myTd.appendChild(myInput);
      myTd.appendChild(myInput);
      myTr.appendChild(myTd);
      if (i == 2) {
        myTd = document.createElement("td");
        myTd.setAttribute("data-name","del");
        myspan = document.createElement("span");
        myspan.textContent = "×";
        mybutton = document.createElement("button");
        mybutton.setAttribute("class", "btn btn-danger row-remove");
        mybutton.setAttribute("value","delete");
        mybutton.setAttribute("onclick","M.mod_bigbluebuttonbn.custom.deleteRow(this)");
        mybutton.setAttribute("type", "button");
        mybutton.setAttribute("id","");
        mybutton.appendChild(myspan);
        myTd.appendChild(mybutton);
        myTr.appendChild(myTd);
        myTr.appendChild(myTd);
      }
    }

    parentTable.appendChild(myTr);
  },
  deleteRow: function (rowIdDelete) {
    let row = rowIdDelete.parentNode.parentNode;
    row.parentNode.removeChild(row);
  },
};