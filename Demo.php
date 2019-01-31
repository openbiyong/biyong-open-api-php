<?php
/**
 * BiYongMerchantCipher 只能在64位系统运行
 * 如果使用32位系统，请修改 BiYongMerchantCipher 中的几个函数的实现
 */
include_once('BiYongMerchantCipher.php');

// 端到端通信流程演示
cryptTest();

// SDK 使用参考
callApi();




function callApi() {
  // BiYong分配给你的AppId
  $appId = "";

  // 你的私钥(Base64-RFC4648)，在开放平台创建应用时，应填写此私钥对应公钥
  $yourPrivateKey = "";

  // BiYong分配给你的RSA公钥(Base64-RFC4648)
  $biyongPublicKey = "";

  // BiYong 开放平台API 已填写开发环境API
  $apiUrl = "https://open.biyong.sg/dev-api/";

  // RSA签名散列算法
  $rsaSignHashMode = "SHA256";

  // AES加密模式(设置为null不使用AES加密。正式环境采用https通信，非隐私数据接口建议关闭AES加密)
  $aesMode = null;

  $client = new HttpClient($appId, $yourPrivateKey, $biyongPublicKey, $apiUrl, $rsaSignHashMode, $aesMode);
  $resp = $client->call("common/test", '{"message":"hello, BiYong"}');
  echo "$resp";
}

class HttpClient {
  var $cipher;
  var $apiUrl;

  function __construct($appId, $yourPrivateKey, $biyongPublicKey, $apiUrl, $shaHashMode='SHA256', $aesMode=null) {
    $this->apiUrl = $apiUrl;
    $this->cipher = new BiYongMerchantCipher($appId, $yourPrivateKey, $biyongPublicKey, $shaHashMode, $aesMode);
  }

  function call($uri, $dataString) {
    $message = $this->cipher->clientEncrypt($dataString);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $this->apiUrl . $uri);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $this->cipher->httpHeaders);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $message->data);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($httpCode == 200) {
      return ord($response[0]) == 0 ? $this->cipher->clientDecrypt($response, $message) : $response;
    } else {
      if ($httpCode == 0) {
        $httpCode = 10000;
      }
      return "{\"status\":{$httpCode},\"message\":\"请求失败\"}";;
    }
    curl_close($ch);
  }
}



function cryptTest() {
  $privateKey1 = "MIIEvgIBADANBgkqhkiG9w0BAQEFAASCBKgwggSkAgEAAoIBAQCGdHa2BsE65r/3YvzhGpsikKp7GsaFpKDex1Fzls8rxFfpa1NhZrwpsjJ09hmp0fwjxbcX8Qe6w7ndH5Sl1f6w5iwPebClKmX+Lfjz36dOBLVhnMfi6xdIKQ4lHNMQPKYE8GLJu7WuUqbeNWEUk73stjtyTFj8rvuzUZioM5YzWMleBvw353VRziOjbYFwrcilopT6EscJ0sB6WR5i4fF8AsFzeEO3FuJKyDGphcBXUN8VDpS2qb2LyZeVYKQVC6Sey7nYfOxIdJMM0dA6RLbi3bU4To6FKTk+3JRIOzQJpSCK+doZHi3aKLSmx5oKgNJf3BLsGJBegcPPDpzGDTodAgMBAAECggEAdaxGHQcKZE+BYLTMlwIfFgBAhB8p8drkRDVzLtOlGyvquMoKnms4cNGZYU3lpf+2SWSH2rdDSYx1BXbXNNB16EJ5+01IcTULMIrxoBZ0qU5rpDN/qTSRGsF7tLVmb4Z00kvEWcQjvJ5vlnhnL4giJ6JRorX5B6TpesYF8ee8I9DU4iQ/qJ8IsqAbFOwQGNXeIYTLMTKob2y68YWdRdi13TYRunDhDw9bb8qfi6Ckp9563SCwb04jp+0SiRhBG6rQQzVSMBHla58NF4xDSO22s9Or995JUOzhkrngqjP5WXZnbZwwbwduf4gz3cPONDGnfS9jaCfhnl0lIEbyeSJ9oQKBgQD5QNIhDBYqxjk6ptjjKKGvXyggzZ5JpFYrGHgyDJjVc4SeqPkBhrVg6xut38UJmJ40tLht3fFUnfgGzFUIg8mCiZPDb3v+UFIDtGdUvg8T9b1THRcfqN2Q2b9nADnbljumk7koKrME2DVp9cZD7ltrjMayXK/b75pibw1hhljflQKBgQCKGChtQ3zXFPUpcxfcW2sDziPPPy3cM1GXdOYJ/S5MxuVylCQBMxRNSWJPtaGqgkBN493i5dAjBl9O44kq5+Lo1RZyMDr8U0go62PVNH+DocDmvTAHFFy/+eiZB1/LexgrYYL3LZQ4TtF1V7XAPyE/bY2yCzN6EN5AM7u3kYnuaQKBgQDbY9/Q8MeOLN3wry1WfMwcBcDXZsT9guXJlwcs3oOj1cMUuBw86Ko7vZWmbMENGkWelLeFFQa3eTf4G+B41y8GdDwYmMdl6KLX2fHd2FCDPBjB0GgrGMK1HcRoT/2dN1YX4AzouvTJvdj+BDPYVTQorUezdPvhtbuJCsCXZ95QJQKBgB4/amN8e9TUt1qL5jcTIx6jQX68tPvdlcqaBWU8uq6AhnORdU159cF0CH+zJiUmAJXPCqQPeIajd67c8gee4TnkqtT6MYFhcJXd8XEa3a9kd89SszlpwWMfh041qkr0vHeMFVa0+hlXUlPkkV/5s/ujsHzGLVFYboYbjsuHqnG5AoGBAPU7WoSEZwwV3yQh2m/wsM0A+He/gKuqSd4JV8XNYzPeGLYmBQAoLvm8qJW4n1oJprk4S2zitOKRMTev/UXkkUSVHrOnzlSjZ33yd3ODIjkNWRFP03i5O4rX5MWCctQNMBg/ZJcbi8qGB2WDR6+3be4uf7zJOWAOonjv3bTiN0Ai";
  $publicKey1 = "MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAhnR2tgbBOua/92L84RqbIpCqexrGhaSg3sdRc5bPK8RX6WtTYWa8KbIydPYZqdH8I8W3F/EHusO53R+UpdX+sOYsD3mwpSpl/i3489+nTgS1YZzH4usXSCkOJRzTEDymBPBiybu1rlKm3jVhFJO97LY7ckxY/K77s1GYqDOWM1jJXgb8N+d1Uc4jo22BcK3IpaKU+hLHCdLAelkeYuHxfALBc3hDtxbiSsgxqYXAV1DfFQ6Utqm9i8mXlWCkFQuknsu52HzsSHSTDNHQOkS24t21OE6OhSk5PtyUSDs0CaUgivnaGR4t2ii0pseaCoDSX9wS7BiQXoHDzw6cxg06HQIDAQAB";
  $privateKey2 = "MIIEvwIBADANBgkqhkiG9w0BAQEFAASCBKkwggSlAgEAAoIBAQDkSN1u1rfjMdgS/Xsv6DYcBE/29K7jnYo40IGGIrqWNKH+8WxIa4hjtBy0skzgdNzBWx2WezJYbW0TOctFhBsln/r8GUdvb+jHnCqLFihR/oFVwBDHliizLnQokrb0uZjyKbjtk6o5dILF6Jpa6iVO3SRWxxbkoH4b6f7dwiyn5WjB/wcXyf9VMEAq3005imEopKwtzSfhpGw9rO45tyJM1e5dFv5uEpfg6Qf85pnCobh8BwJkv1ZedSiounyKr6zvmyTG3UdyUvsiCgONalaytlH/+IxlKz+KXTwNR2HK4rH5BYoCjR8GhUCMFB/9cRlqEyqDm1f/QzW5APNcwLbdAgMBAAECggEAbZEnbo5yHgKLYbn1yS2b4uCS/MW9txOjBtfUguviQDus0O9Q+IVcJfaJnJTDXyvX1JoF3nbs2BJVOtgPXyMj4HAjh6Iebjb5M+0ZYj5VRd1weBbCNvk0OaP/LoYUd+sopHov/x9ToVXxeknE5APjujFbwqa1ry/0tzMdF5Sd2ErUGl2MTLy8gKmKtMTUegOAIYTKcECCFV4isecPYUDwrxCO113DkgP+IERADMleBHTiZp6rMHOCk4JdSIdNscvLfH9A1nx6QkVwVMYAzbIEtI4LfGtEgYNxta7KLFtPjceRDx+bl8+AWgQU3i9viJWTnpTm0Ku2f9UkllWwYpQqwQKBgQD+fis/LlWq+4nePRZ267DPBoYkvqfdHFHFTjhj+OWCwID3fvcObi/alKJXIWWu0xkNoVx4s82blA3SvnjcyrlIkqG8c6ep99ICq8e+DVeAAqIB/hSprMo3a11o8RZRwHOHYdkFrcGm2B4oDcOI53YWKFKm178B1Y5cMkuKHGmLrQKBgQDlovZbQEKyw1JALwLY3nkioEKdsWLh4aKjSfmQN1vylAdvyrFmP0n+KzCLCXmmyuLxKNZCkR/urJN9P/WTNRsLyc5NfoEYPk4RGIYV+Sc00HcEa5rG79HUv8gZyvK2p1Zo96TAPHOSNmQsw7fpgWKGq3GjzEOrzyi/jVCf9ww98QKBgQC0C1TTPReUgKJ4HOWwumv6+xWaF1ww/OEI4p7Yc3UD/OcAsc1dYyztyevUEqeaeHQoBXmjVylmIOdqqiBdq/pLUpmj9nqur8ne4+LLHStDQBmXqUa7B6iEbqvGG5H7wli5dcsQzm3LeOhU0+/7Ai2z3VEkAkx6org1l8uDaThufQKBgQChSRoa8UFnaQRGDD7Fr0wJY+Il+8blu6KNaZGdFyS/dfTbMdPzapQ/rnoDzX3iBjHrC7GhQ2jYK+HTYK7M28nJN85sY2OscWZHX6AdosdEsv5E3obxHtOTx7d1VjOu0k1AoF7YnhzWHtmxDy4HFVbsG1JPp1IIRBHsqAZutAenIQKBgQD82qngAIZYmHPKW1dzPn1JvHX7qriQsDLxPu7Y/8Nw0gsiaVXvsRAJBkz+SmSvCfdI9xZb+0D31UPATE6iqJQHfBYVAZFFAYC7eRVqVl3wgUP8/OV6mt43B0S0IHafMAEoJsbw/YNmosEVs97S1ji7V4Kz2Kj6no8xVi/zPcJfTw==";
  $publicKey2 = "MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA5Ejdbta34zHYEv17L+g2HARP9vSu452KONCBhiK6ljSh/vFsSGuIY7QctLJM4HTcwVsdlnsyWG1tEznLRYQbJZ/6/BlHb2/ox5wqixYoUf6BVcAQx5Yosy50KJK29LmY8im47ZOqOXSCxeiaWuolTt0kVscW5KB+G+n+3cIsp+Vowf8HF8n/VTBAKt9NOYphKKSsLc0n4aRsPazuObciTNXuXRb+bhKX4OkH/OaZwqG4fAcCZL9WXnUoqLp8iq+s75skxt1HclL7IgoDjWpWsrZR//iMZSs/il08DUdhyuKx+QWKAo0fBoVAjBQf/XEZahMqg5tX/0M1uQDzXMC23QIDAQAB";
  $rsaSignHashMode = "SHA256";
  $aesMode = "CFB/NoPadding";

  $client1 = new BiYongMerchantCipher("fakeAppId", $privateKey1, $publicKey2, $rsaSignHashMode, $aesMode);
  $client2 = new BiYongMerchantCipher("fakeAppId", $privateKey2, $publicKey1, $rsaSignHashMode, $aesMode);

  // 客户端1发起请求
  $message = $client1->clientEncrypt('{"message":"hello, BiYong"}');
  echo "client1 send message:<br>";
  $message->echoData();

  //$message->data = hex2bin("00000100b685049fe30a451b208a60744a0d4ab3aca31b6324bf6c05d6c660e0acc54290c1259c8d2a762cdc972056421d165a2c58f6ded28ef3621a2d8bc5268ec678aa83b5cdcd3b804a13ee298aabf512f5a0b6f49e3b5d0d43d42a479aa84c873aa148a2d4a87b1bf40aed792dee216daf2dd0f07f3eead51cb22adfefab8a9ddb87d1bcc9af2695a51c4315c5303694bf8d091fa0a66a1537328d58742a38d472fac2a07e76181339aae7e88e1f774777f3e84d576b02c9c659916e9897896732a1abb327c1b55ca011d0abfd9c7aafdfe81c7037caf9c88d5169fbfb75e3c25980995ae29e90d83fc5f5fc6fbf614144ad43b31936bad929d94563565e4efb302e50d64c3fbf3f5052c3b5c441915bb7b710883586a3814a7e8041db6b4eeb87f573ab89449c455dc92c97c93398c8d829576527c736ed5985759fc5572bbd252fe978403d2b36ff72b360cac83700310df27c498a22684eba31111307ec2a53bcb1a73e502b9898ea4165694415135b550227c2decdec1c380b71ba407606ef3f8e320c7c0da65e37d5d9cf31d22fdeefce290933bb5fd15d5ca59b317a490f9b5bd15c2380e0f5881a10c0a1f37af201337ddd99614af683e5fee3d11ca402b195c17a720bd5afad3a8b1d0b4c85210515cba17b74248f34dacdc6ccf01c70aa47e1afcda24dc7e27bbe7b7d3e15f5d94ba119f9b34feb89d82487ca04b767ac280752ea2d3e9d392dce1191f5b67c7f336665a7dd95634e68f12bfc1ab3606122bf1b95663f71de80907898dd30ebce2b36ad849b9f95");

  // 客户端2(作为服务端)收到请求，解析并验证
  $serverMessage = $client2->serverDecrypt($message->data);
  echo "<br>client2 receive message:<br>";
  $serverMessage->echoData();

  // 客户端2返回结果
  $responseStr = '{"timestamp":"1525616709780","status":"0","data":{"message":"hello, Merchant"}}';
  $responseData = $client2->serverEncrypt($responseStr, $serverMessage);

  // 客户端1拿到结果，解析并验证
  $respData = $client1->clientDecrypt($responseData, $message);
  echo "<br>client1 get response: $respData<br>";
}

?>