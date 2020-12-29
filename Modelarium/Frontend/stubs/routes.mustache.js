/**
 * This file was automatically generated by Modelarium
 */

import * as components from './index';

export default [
    {
        path: '/{|routeName|}',
        name: '{|StudlyName|}List',
        component: components.{| StudlyName |}List
    },
    {
        path: '/{|routeName|}/:{|keyAttribute|}?/edit',
        name: '{|StudlyName|}Edit',
        component: components.{| StudlyName |}Edit
    },
    {
        path: '/{|routeName|}/table',
        name: '{|StudlyName|}Table',
        component: components.{| StudlyName |}Table
    },
    {
        path: '/{|routeName|}/:{|keyAttribute|}',
        name: '{|StudlyName|}Show',
        component: components.{| StudlyName |}Show
    }
];

