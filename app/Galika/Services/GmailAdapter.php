<?php
namespace App\Galika\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class GmailAdapter
{
    public function __construct(private ConnectionVault $vault,private OAuthService $oauth){}

    private function token(int $userId):string
    {
        if($this->vault->expiring($userId,'gmail')) return $this->oauth->refresh($userId,'gmail');
        return $this->vault->credential($userId,'gmail','access_token');
    }

    public function search(int $userId,string $query,int $max=100):array
    {
        $r=Http::withToken($this->token($userId))->timeout(30)
            ->get('https://gmail.googleapis.com/gmail/v1/users/me/messages',['q'=>$query,'maxResults'=>$max]);
        if(!$r->successful()) throw new RuntimeException('Gmail search failed '.$r->status().': '.$r->body());
        return $r->json('messages')??[];
    }

    public function read(int $userId,string $id):array
    {
        $r=Http::withToken($this->token($userId))->timeout(30)
            ->get("https://gmail.googleapis.com/gmail/v1/users/me/messages/{$id}",['format'=>'full']);
        if(!$r->successful()) throw new RuntimeException('Gmail read failed '.$r->status());
        return $r->json();
    }

    public function send(int $userId,string $from,string $to,string $subject,string $body,?string $threadId=null,?string $messageId=null):array
    {
        $headers=["From: {$from}","To: {$to}","Subject: {$subject}",'MIME-Version: 1.0','Content-Type: text/plain; charset=UTF-8'];
        if($messageId){$headers[]="In-Reply-To: {$messageId}";$headers[]="References: {$messageId}";}
        $raw=implode("\r\n",$headers)."\r\n\r\n{$body}";
        $payload=['raw'=>rtrim(strtr(base64_encode($raw),'+/','-_'),'=')];
        if($threadId)$payload['threadId']=$threadId;

        $r=Http::withToken($this->token($userId))->timeout(30)
            ->post('https://gmail.googleapis.com/gmail/v1/users/me/messages/send',$payload);
        if(!$r->successful()) throw new RuntimeException('Gmail send failed '.$r->status().': '.$r->body());
        return $r->json();
    }

    public function body(array $message):string{return $this->walk($message['payload']??[]);}

    public function header(array $message,string $name):?string
    {
        foreach(data_get($message,'payload.headers',[]) as $h) if(strcasecmp($h['name']??'',$name)===0) return $h['value']??null;
        return null;
    }

    private function walk(array $p):string
    {
        $s='';
        if(!empty($p['body']['data'])) $s.=base64_decode(strtr($p['body']['data'],'-_','+/'))?:'';
        foreach($p['parts']??[] as $c) $s.="\n".$this->walk($c);
        return trim($s);
    }
}
