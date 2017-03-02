#!/usr/bin/env node

/*
 *
 * Copyright 2017 Maxime Désécot - Cloud Temple
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * Description: Example of script in NodeJS to use the MyZimbra.net API
 *
 */

const constants   = require('constants');
const extend      = require('util')._extend;
const http_client = require('https');

const base_api    = "/api/v1";
const host_api    = "api.myzimbra.net";
const method_api  = "POST";
const port        = 443;

process.env.NODE_TLS_REJECT_UNAUTHORIZED = "0";

var MyZimbra = function () {

    this.session = null;
    this.default_options = {
        request: {
            host: host_api,
            port: port,
            method: method_api,
            headers: {
                "content-type": "application/json; charset=utf-8",
                "Accept": 'application/json'
            },
            agent: false,
            secureOptions: constants.SSL_OP_NO_TLSv1_2,
            strictSSL: false
        }, body: null
    };

    this.login = function (login, password) {

        var body = {
            login: login,
            password: password
        };

        var options = this._getOptions("auth", body);
        return this._doRequest(options)
            .then(function (data) {
                this._loginHandler(data);
            }.bind(this))
            .catch(function (data) {
                console.log(data);
            });

    };

    this._loginHandler = function (body) {
        this.session = body.data.session;
        this.session.end_at = new Date().getTime() + (this.session.life_time * 1000);
        this.default_options.request.headers['Authorization'] = "Token token=" + this.session.token;
    };

    this.getAllAccounts = function (domain, offset, limit, sortby, sortascending) {

        var body = {
            offset: offset,
            limit: limit,
            sortby: sortby,
            sortascending: sortascending
        };
        var path = "accounts/" + domain;

        var options = this._getOptions(path, body);
        options.request.method = "GET";
        return this._doRequest(options);

    };

    this.createAccount = function(new_account){

        var path = "accounts";
        var options = this._getOptions(path, new_account);

        options.request.method = "POST";

        return this._doRequest(options);
    };

    this._getOptions = function (api_path, body) {

        body = body || null;

        var options = extend({}, this.default_options);
        options.request.path = [base_api, api_path].join('/');

        if (body != null) {
            var req = JSON.stringify(body);
            options.request.headers["Content-Length"] = Buffer.byteLength(req);
            options.body = req;
        }

        return options;
    };

    this._doRequest = function (options) {

        return new Promise(function (resolve, reject) {

            var req = http_client.request(options.request, function (response) {

                response.setEncoding('utf8');
                var responseData = '';

                response.on('data', function (data) {
                    responseData += data;
                });

                response.on('end', function () {
                    var res = JSON.parse(responseData);
                    if (response.statusCode < 200 || response.statusCode > 299) {
                        reject(res);
                    }
                    else {
                        resolve(res);
                    }
                });

            });

            req.on('timeout', function () {
                req.abort();
            });

            req.on('error', function (err) {
                reject(err, null, true);
            });

            if (options.body != null) {
                req.write(options.body);
            }

            req.setTimeout(30000);
            req.end();

        });

    };

};

var conn = new MyZimbra();

// replace login and password by your credential MyZimbra
conn.login("login", "password").then(function () {

    conn.getAllAccounts("domain.com", 0, 10, "name", 1)
        .then(function (response) {
            for(var i=0; i<response.data.accounts.length; i++){
                var account = response.data.accounts[i];
                console.log(account["name"]);
            }
        });

    conn.createAccount({
        name:        "jon.snow@domain.com",
        password:    "myzimbra!",
        accountType: "UPRO",
        company:     "CLOUD-TEMPLE",
        description: "Nouveau compte de test",
        displayName: "Jon SNOW",
        mobile:      "0666066606"
        }).then(function(response){
            var new_account = response.data;
            console.log(new_account["id"]);
        });

});