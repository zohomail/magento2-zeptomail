define([
    'jquery',
    'Magento_Ui/js/model/messageList'
], function ($, messageList) {
    'use strict';

    return {
        addSuccessMessage: function (message) {
            messageList.addSuccessMessage({ message: message });
        },
        addErrorMessage: function (message) {
            messageList.addErrorMessage({ message: message });
        }
    };
},function(error){
	console.log(error);
});