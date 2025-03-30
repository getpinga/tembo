<?php
/**
 * PlexEPP: EPP server benchmark
 *
 * Written in 2025 by Taras Kondratyuk (https://namingo.org)
 * Based on xpanel/epp-bundle written in 2019 by Lilian Rudenco (info@xpanel.com)
 *
 * @license MIT
 */

// Function to generate random strings
function randomString($length) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

// Function to generate a random domain name
function randomDomain() {
    return randomString(8) . '.test';
}

// Function to perform domain check
function performDomainCheck($epp, $domains) {
    try {
        $params = array('domains' => $domains);
        $domainCheck = $epp->domainCheck($params);

        if (array_key_exists('error', $domainCheck)) {
            echo 'DomainCheck Error: ' . $domainCheck['error'] . PHP_EOL;
        } else {
            echo "DomainCheck result: " . $domainCheck['code'] . ": " . $domainCheck['msg'] . PHP_EOL;
            $x = 1;
            foreach ($domainCheck['domains'] as $domain) {
                if ($domain['avail']) {
                    echo "Domain " . $x . ": " . $domain['name'] . " is available" . PHP_EOL;
                } else {
                    echo "Domain " . $x . ": " . $domain['name'] . " is not available because: " . $domain['reason'] . PHP_EOL;
                }
                $x++;
            }
        }
    } catch (\Pinga\Tembo\Exception\EppException $e) {
        echo "Error : " . $e->getMessage() . PHP_EOL;
    } catch (Throwable $e) {
        echo "Error : " . $e->getMessage() . PHP_EOL;
    }
}

// Function to perform domain create operation
function performDomainCreate($epp, $domain) {
    try {
        $params = array(
            'domainname' => $domain,
            'period' => 1,
            'nss' => array('ns1.example.com','ns2.example.com'),
            'registrant' => 'tembo007',
            'contacts' => array(
               'admin' => 'tembo007',
               'tech' => 'tembo007',
               'billing' => 'tembo007'
            ),
            'authInfoPw' => 'Domainpw123@'
        );
        $domainCreate = $epp->domainCreate($params);
        
        if (array_key_exists('error', $domainCreate)) {
            echo 'DomainCreate Error: ' . $domainCreate['error'] . PHP_EOL;
        } else {
            echo 'DomainCreate Result: ' . $domainCreate['code'] . ': ' . $domainCreate['msg'] . PHP_EOL;
            echo 'New Domain: ' . $domainCreate['name'] . PHP_EOL;
            echo 'Created On: ' . $domainCreate['crDate'] . PHP_EOL;
            echo 'Expires On: ' . $domainCreate['exDate'] . PHP_EOL;
        }
    } catch (\Pinga\Tembo\Exception\EppException $e) {
        echo "Error : " . $e->getMessage() . PHP_EOL;
    } catch (Throwable $e) {
        echo "Error : " . $e->getMessage() . PHP_EOL;
    }
}

// Function to perform domain info operation
function performDomainInfo($epp, $domain) {
    try {
        $params = array(
            'domainname' => $domain,
            'authInfoPw' => 'P@ssword123!'
        );
        $domainInfo = $epp->domainInfo($params);
        
        if (array_key_exists('error', $domainInfo)) {
            echo 'DomainInfo Error: ' . $domainInfo['error'] . PHP_EOL;
        } else {
            echo 'DomainInfo Result: ' . $domainInfo['code'] . ': ' . $domainInfo['msg'] . PHP_EOL;
            echo 'Name: ' . $domainInfo['name'] . PHP_EOL;
            echo 'ROID: ' . $domainInfo['roid'] . PHP_EOL;
            $status = $domainInfo['status'] ?? 'No status available';
            if (is_array($status)) {
                echo 'Status: ' . implode(', ', $status) . PHP_EOL;
            } else {
                echo 'Status: ' . $status . PHP_EOL;
            }
            echo 'Registrant: ' . $domainInfo['registrant'] . PHP_EOL;

            $contact_types = array("admin", "billing", "tech");
            foreach ($contact_types as $type) {
                $contact = array_values(array_filter($domainInfo['contact'], function($c) use ($type) {
                    return $c["type"] == $type;
                }));
                if (count($contact) > 0) {
                    $type = ucfirst($type);
                    echo $type . ": " . $contact[0]["id"] . "\n";
                }
            }
            asort($domainInfo['ns']);
            foreach ($domainInfo['ns'] as $server) {
                echo "Name Server: $server\n";
            }
            asort($domainInfo['host']);
            foreach ($domainInfo['host'] as $host) {
                echo "Host: $host\n";
            }
            echo 'Current Registrar: ' . $domainInfo['clID'] . PHP_EOL;
            echo 'Original Registrar: ' . $domainInfo['crID'] . PHP_EOL;
            echo 'Created On: ' . $domainInfo['crDate'] . PHP_EOL;
            echo 'Updated By: ' . $domainInfo['upID'] . PHP_EOL;
            echo 'Updated On: ' . $domainInfo['upDate'] . PHP_EOL;
            echo 'Expires On: ' . $domainInfo['exDate'] . PHP_EOL;
            echo 'Transferred On: ' . $domainInfo['trDate'] . PHP_EOL;
            echo 'Password: ' . $domainInfo['authInfo'] . PHP_EOL;
        }
    } catch (\Pinga\Tembo\Exception\EppException $e) {
        echo "Error : " . $e->getMessage() . PHP_EOL;
    } catch (Throwable $e) {
        echo "Error : " . $e->getMessage() . PHP_EOL;
    }
}