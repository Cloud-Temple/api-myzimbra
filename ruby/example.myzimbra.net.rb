#!/usr/bin/env ruby
#encoding: UTF-8

#
# Copyright 2017 Maxime Désécot - Cloud Temple
#
# Licensed under the Apache License, Version 2.0 (the "License");
# you may not use this file except in compliance with the License.
# You may obtain a copy of the License at
#
# http://www.apache.org/licenses/LICENSE-2.0
#
# Unless required by applicable law or agreed to in writing, software
# distributed under the License is distributed on an "AS IS" BASIS,
# WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
# See the License for the specific language governing permissions and
# limitations under the License.
#
# Description: Example of script in Ruby to use the MyZimbra.net API
#

require 'active_support/all'
require 'json'
require 'net/https'
require 'uri'

class MyZimbra

  API_PATH    = "/api/v1";
  API_HOST    = "api.myzimbra.net";
  API_METHOD  = "POST";
  API_PORT    = 443;
  API_SCHEME  = "https";

  def initialize
    @json_headers = {"Content-Type" => "application/json", "Accept" => "application/json"}
  end

  def login(login, password)
    response = curl_request(:post, construct_uri("auth"), {login: login, password: password})
    @json_headers["Authorization"] = "Token token=#{response["data"]["session"]["token"]}"
  end

  def get_all_accounts(domain, offset, limit, sortby, sortascending)

    params = {
        offset: offset,
        limit: limit,
        sortby: sortby,
        sortascending: sortascending
    }

    uri = construct_uri("accounts/"+domain, params.to_query)

    curl_request(:get, uri, {})

  end

  def create_account(body)
    curl_request(:post, construct_uri("accounts"), body)
  end

  private
  def construct_uri(path, query = nil)
    URI::HTTP.new( API_SCHEME, nil, API_HOST, API_PORT, nil, File.join(API_PATH, path), nil, query, nil )
  end

  def curl_request(method, uri, body)

    http = Net::HTTP.new(uri.host, uri.port)
    http.use_ssl = uri.scheme == 'https'
    http.verify_mode = OpenSSL::SSL::VERIFY_NONE

    case method
      when :get
        req = Net::HTTP::Get.new uri
      when :post
        req = Net::HTTP::Post.new uri
      when :put
        req = Net::HTTP::Put.new uri
      when :delete
        req = Net::HTTP::Delete.new uri
      else
        raise
    end

    req.initialize_http_header(@json_headers)
    req.body = body.to_json

    res = http.request(req)
    body = JSON.parse(res.body)

    raise StandardError.new(body) if !res.kind_of?(Net::HTTPSuccess)
    body

  end

end

conn = MyZimbra.new

# replace login and password by your credential MyZimbra
conn.login("login", "password")

response = conn.get_all_accounts("domain.com", 0, 10, "name", 1)
accounts = response["data"]["accounts"]
accounts.each do |account|
  puts account["name"]
end

response = conn.create_account({
   name:        "jon.snow@domain.com",
   password:    "myzimbra!",
   accountType: "UPRO",
   company:     "CLOUD-TEMPLE",
   description: "Nouveau compte de test",
   displayName: "Jon SNOW",
   mobile:      "0666066606"
})
account = response["data"]
puts account["id"]