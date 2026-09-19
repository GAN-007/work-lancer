<?php
namespace App\Galika\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class GmailAdapter{
    private function token(int $userId):string{$t=config('services.gmail.access_token');if(!$t)throw new RuntimeException('GMAIL_ACCESS_TOKEN missing for runtime credential provider');return $t;}
    public function search(int $userId,string $query,int $max=100):array{$r=Http::withToken($this->token($userId))->timeout(30)->get('https://gmail.googleapis.com/gmail/v1/users/me/messages',['q'=>$query,'maxResults'=>$max]);if(!$r->successful())throw new RuntimeException('Gmail search failed '.$r->status().': '.$r->body());return $r->json('messages')??[];}
    public function read(int $userId,string $id):array{$r=Http::withToken($this->token($userId))->timeout(30)->get("https://gmail.googleapis.com/gmail/v1/users/me/messages/{$id}",['format'=>'full']);if(!$r->successful())throw new RuntimeException('Gmail read failed '.$r->status());return $r->json();}
    public function send(int $userId,string $from,string $to,string $subject,string $body):array{$raw="From: {$from}\r\nTo: {$to}\r\nSubject: {$subject}\r\nMIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\n\r\n{$body}";$encoded=rtrim(strtr(base64_encode($raw),'+/','-_'),'=');$r=Http::withToken($this->token($userId))->timeout(30)->post('https://gmail.googleapis.com/gmail/v1/users/me/messages/send',['raw'=>$encoded]);if(!$r->successful())throw new RuntimeException('Gmail send failed '.$r->status().': '.$r->body());return $r->json();}
    public function body(array $message):string{return $this->walk($message['payload']??[]);}
    private function walk(array $p):string{$s='';if(!empty($p['body']['data']))$s.=base64_decode(strtr($p['body']['data'],'-_','+/'))?:'';foreach($p['parts']??[] as $c)$s.="
".$this->walk($c);return trim($s);}
}
