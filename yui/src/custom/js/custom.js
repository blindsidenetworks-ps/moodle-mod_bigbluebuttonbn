M.mod_bigbluebuttonbn = M.mod_bigbluebuttonbn || {};
M.mod_bigbluebuttonbn.custom = {
    bigbluebuttonbn: {},

    init: function (bigbluebuttonbn) {
        this.bigbluebuttonbn = bigbluebuttonbn;
        let mainParent = Y.one("#admin-bigbluebuttonbn_enable_cluster");
        let ele = document.getElementById('id_s__bigbluebuttonbn_enable_cluster');
        Y.DOM.setAttribute(ele, 'onchange', 'M.mod_bigbluebuttonbn.custom.showCluster(this);');
        mainParent.insert(this.table, 'after');
        this.showCluster(ele);
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
                                                    <input type="text" name="bigbluebuttonbn_cluster[0][server_name]"  placeholder="Server Name" class="form-control"/>
                                                </td>
                                                <td data-name="server_url">
                                                    <input type="text" name="bigbluebuttonbn_cluster[0][server_url]" placeholder="Server Url" class="form-control"/>
                                                </td>
                                                <td data-name="shared_secret">
                                                    <input type="text" name="bigbluebuttonbn_cluster[0][shared_secret]" placeholder="Shared Secret" class="form-control"/>
                                                </td>
                                                <td data-name="del">
                                                    <a class='btn btn-danger row-remove'><span>×</span></a>
                                                </td>
                                            </tr>
                                            <tr data-index="1">
                                                <td data-name="server_name">
                                                    <input type="text" name="bigbluebuttonbn_cluster[1][server_name]"  placeholder="Server Name" class="form-control"/>
                                                </td>
                                                <td data-name="server_url">
                                                    <input type="text" name="bigbluebuttonbn_cluster[1][server_url]" placeholder="Server Url" class="form-control"/>
                                                </td>
                                                <td data-name="shared_secret">
                                                    <input type="text" name="bigbluebuttonbn_cluster[1][shared_secret]" placeholder="Shared Secret" class="form-control"/>
                                                </td>
                                                <td data-name="del">
                                                    <a class='btn btn-danger row-remove'><span>×</span></a>
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
        Y.one('#admin-bigbluebuttonbn_server_url').hide();
        Y.one('#admin-bigbluebuttonbn_shared_secret').hide();
        Y.one('#cluster_table').show();
    },

    disableCluster: function () {
        Y.one('#admin-bigbluebuttonbn_server_url').show();
        Y.one('#admin-bigbluebuttonbn_shared_secret').show();
        Y.one('#cluster_table').hide();
    }
};