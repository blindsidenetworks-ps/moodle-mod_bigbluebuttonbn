YUI().use("datasource-function", "datasource-polling", function(Y) {

    var getDateString = function() {
        return new Date();
    };

    var dataSource = new Y.DataSource.Function({source:getDateString});

    var request = {
        callback : {
            success: function(e) {
                console.debug('success');
                document.getElementById('txtUpdate').innerHTML = e.response.results[0];
            },
            failure: function(e) {
                console.debug('failure');
            }
        }
    };

    var id = dataSource.setInterval(1000, request );

});
