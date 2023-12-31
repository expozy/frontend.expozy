import {ApiClass} from '../core/api/api.js';
import {Helpers} from '../core/helpers.js';
import {Handler} from '../core/handler.js';

export let Warehouse = {

  get_warehouses: async function(data, options){
    let response = [];


    let api = new ApiClass();
    await api.get('accounts', false);

    if(!api.response) return response['internalError'] = 'No response from api for Warehouse.get_warehouses';


    response['obj'] = api.response;
    response.keyName = 'warehouses';

    if("keyName" in options && options['keyName'] != '' && options['keyName'] != null) response.keyName = options['keyName'];

    if("initial" in options && options['initial'] == true) return Handler.responseHandler(response);

    return response;
  },

};

window.Warehouse = Warehouse;
