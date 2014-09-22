bigbluebuttonbn_set_participant_selection = function() {
    bigbluebuttonbn_select_clear('bigbluebuttonbn_participant_selection');

    var type = document.getElementById('bigbluebuttonbn_participant_selection_type');
    for( var i = 0; i < type.options.length; i++ ){
        if( type.options[i].selected ) {
            var options = bigbluebuttonbn_participant_selection[type.options[i].value];
            for( var j = 0; j < options.length; j++ ) {
                bigbluebuttonbn_select_add_option('bigbluebuttonbn_participant_selection', options[j].name, options[j].id);
            }
            if( j == 0){
                bigbluebuttonbn_select_add_option('bigbluebuttonbn_participant_selection', '---------------', 0);
                bigbluebuttonbn_select_disable('bigbluebuttonbn_participant_selection')
            } else {
                bigbluebuttonbn_select_enable('bigbluebuttonbn_participant_selection')
            }
        }
            
    }
}

bigbluebuttonbn_select_clear = function(id) {
    var select = document.getElementById(id);
    while( select.length > 0 ){
        select.remove(select.length-1);
    }
}

bigbluebuttonbn_select_enable = function(id) {
    var select = document.getElementById(id);
    select.disabled = false;
}

bigbluebuttonbn_select_disable = function(id) {
    var select = document.getElementById(id);
    select.disabled = true;
}

bigbluebuttonbn_select_add_option = function(id, text, value) {
    var select = document.getElementById(id);
    var option = document.createElement('option');
    option.text = text; 
    option.value = value;
    select.add(option , 0);
}
