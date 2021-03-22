<?php

namespace Telegram;

class Message
{
  private static $jsonMsg;
  private static $jsonString;

  public function __construct ($jsonMessage = null)
  {
    if (!empty ($jsonMessage)) {
      self::setMessageJson ($jsonMessage);
    }
  }

  public static function setMessageJson ($jsonMessage = null)
  {
    if (empty ($jsonMessage)) {
      throw new Exception ('A mensagem não pode ser vazia.');
      die();
    }

    self::$jsonString = $jsonMessage;
    self::$jsonMsg = json_decode (self::$jsonString);

    return self::$jsonMsg;
  }

  public static function getJson ($jsonMessage = null)
  {
    if (!empty ($jsonMessage)) {
      return $jsonMessage;
    } else if (empty (self::$jsonMsg)) {
      throw new Exception ('A mensagem não pode estar vazia.');
      die();
    }

    return self::$jsonMsg;
  }

  public static function isPrivateChat ($jsonMessage = null)
  {
    $chat = getMessageChat ($jsonMessage);

    return ($chat['type'] == 'private');
  }

  public static function isGroupChat ($jsonMessage = null)
  {
    $chat = getMessageChat ($jsonMessage);

    return ($chat['type'] == 'group');
  }

  public static function isEditedMessage ($jsonMessage = null)
  {
    $json = self::getJson ($jsonMessage);

    return isset ($json->edited_message);
  }

  public static function isReplyingMessage ($jsonMessage = null)
  {
    $msg = self::getMessageInfo ($jsonMessage);

    return isset ($msg->reply_to_message);
  }

  public static function isLeftUserMessage ($jsonMessage = null)
  {
    $msg = self::getMessageInfo ($jsonMessage);

    return (isset ($msg->left_chat_members) || isset ($msg->left_chat_participant) || isset ($msg->left_chat_member));
  }

  public static function isNewUserMessage ($jsonMessage = null)
  {
    $msg = self::getMessageInfo ($jsonMessage);

    return (isset ($msg->new_chat_members) || isset ($msg->new_chat_participant) || isset ($msg->new_chat_member));
  }

  public static function getUpdateId ($jsonMessage = null)
  {
    $json = self::getJson();
    
    return $json->update_id;
  }

  public static function getMessageId ($jsonMessage = null)
  {
    $json = self::getJson ($jsonMessage);

    if (self::isEditedMessage ($jsonMessage)) {
      return $json->edited_message->message_id;
    }
    
    return $json->message->message_id;
  }

  public static function getFromId ($jsonMessage = null)
  {
    $from = self::getMessageFrom ($jsonMessage);

    return $from['id'];
  }

  public static function getFromFullName ($jsonMessage = null)
  {
    $from = self::getMessageFrom ($jsonMessage);

    $name = "{$from['first_name']} {$from['last_name']}";
    $name = trim ($name);

    if (empty ($name)) {
      $name = trim ($from['title']);
    }

    return $name;
  }

  public static function getFromName ($jsonMessage = null)
  {
    $from = self::getMessageFrom ($jsonMessage);

    $name = trim ($from['first_name']);

    if (empty ($name)) {
      return trim ($from['title']);
    }

    return $name;
  }

  public static function getFromFirstName ($jsonMessage = null)
  {
    $from = self::getMessageFrom ($jsonMessage);

    return trim ($from['first_name']);
  }

  public static function getFromLastName ($jsonMessage = null)
  {
    $from = self::getMessageFrom ($jsonMessage);

    return trim ($from['last_name']);
  }

  public static function getFromTitle ($jsonMessage = null)
  {
    $from = self::getMessageFrom ($jsonMessage);

    return trim ($from['title']);
  }

  public static function getChatId ($jsonMessage = null)
  {
    $chat = self::getMessageFrom ($jsonMessage);

    return $chat['id'];
  }

  public static function getChatFullName ($jsonMessage = null)
  {
    $chat = self::getMessageChat ($jsonMessage);

    $name = "{$chat['first_name']} {$chat['last_name']}";
    $name = trim ($name);

    if (empty ($name))
    { $name = trim ($chat['title']); }

    return $name;
  }

  public static function getChatName ($jsonMessage = null)
  {
    $chat = self::getMessageChat ($jsonMessage);

    $name = trim ($chat['first_name']);

    if (empty ($name)) {
      return trim ($chat['title']);
    }

    return $name;
  }

  public static function getChatFirstName ($jsonMessage = null): string
  {
    $chat = self::getMessageChat ($jsonMessage);

    return trim ($chat['first_name']);
  }

  public static function getChatLastName ($jsonMessage = null): string
  {
    $chat = self::getMessageChat ($jsonMessage);

    return trim ($chat['last_name']);
  }

  public static function getChatTitle ($jsonMessage = null): string
  {
    $chat = self::getMessageChat ($jsonMessage);

    return trim ($chat['title']);
  }

  public static function getDate ($jsonMessage = null)
  {
    $msg = self::getMessageInfo ($jsonMessage);

    return $msg->date ?? 0;
  }

  public static function getEditDate ($jsonMessage = null)
  {
    $msg = self::getMessageInfo ($jsonMessage);

    return $msg->edit_date ?? 0;
  }

  public static function getText ($jsonMessage = null)
  {
    $msg = self::getMessageInfo ($jsonMessage);

    return $msg->text ?? '';
  }

  public static function getEntities ($jsonMessage = null)
  {
    $msg = self::getMessageInfo ($jsonMessage);
    $_entities = $msg->entities ?? [];
    $entities = [];

    if (empty ($_entities)) {
      return $entities;
    }

    $text = self::getText ($jsonMessage);

    foreach ($_entities as $_entity) {
      $entity = self::getEntityData ($_entity);
      $command = trim (substr ($text, $entity['offset'], $entity['length']));
      $text = str_replace ($command, '|', $text);
      $entity['entity'] = $command;
      $entities[] = $entity;
    }

    $text_explode = explode ('|', $text);
    $text_explode = array_slice ($text_explode, 1 - count ($text_explode));

    foreach ($text_explode as $k => $param) {
      $entities[$k]['param'] = trim ($param);
    }

    return $entities;
  }

  public static function getBotCommands ($jsonMessage = null)
  {
    $entities = self::getEntities ($jsonMessage);
    $botCommands = [];

    if (empty ($entities)) {
      return $botCommands;
    }

    foreach ($entities as $_entity) {
      $entity = self::getEntityData ($_entity);

      if ($entity['type'] == 'bot_command') {
        $botCommands[] = $entity;
      }
    }

    return $botCommands;
  }

  public static function getBotCommand ($jsonMessage = null)
  {
    $botCommand = self::getBotCommands ($jsonMessage)[0] ?? '';

    if (empty ($botCommand)) {
      return '';
    }

    $text = self::getText ($jsonMessage);

    $command = trim (substr ($text, $botCommand['offset'], $botCommand['length']));

    return $command;
  }

  public static function getMessageInfo ($jsonMessage = null)
  {
    $json = self::getJson ($jsonMessage);

    return  (self::isEditedMessage ($json)) ?
      $json->edited_message :
      $json->message ?? $json;
  }

  public static function getReplyMessageInfo ($jsonMessage = null)
  {
    $msg = self::getMessageInfo ($jsonMessage);

    return  (self::isReplyingMessage ($msg)) ?
      $msg->reply_to_message :
      null;
  }

  public static function getMessageFrom ($jsonMessage = null)
  {
    $msg = self::getMessageInfo ($jsonMessage);

    return self::getFromData ($msg->from);
  }

  public static function getReplyMessageFrom ($jsonMessage = null)
  {
    $msg = self::getReplyMessageInfo ($jsonMessage);

    return self::getFromData ($msg->from);
  }

  public static function getMessageChat ($jsonMessage = null)
  {
    $msg = self::getMessageInfo ($jsonMessage);

    return self::getChatData ($msg->chat);
  }

  public static function getReplyMessageChat ($jsonMessage = null)
  {
    $msg = self::getReplyMessageInfo ($jsonMessage);

    return self::getChatData ($msg->chat);
  }

  public static function getMessageFromAndChat ($jsonMessage = null)
  {
    $msg = self::getMessageInfo ($jsonMessage);

    return [
      "from" => self::getMessageFrom ($msg),
      "chat" => self::getMessageChat ($msg)
    ];
  }

  public static function getQtdLeftUser ($jsonMessage = null)
  {
    $msg = self::getMessageInfo ($jsonMessage);

    if (!self::isLeftUserMessage ($jsonMessage)) {
      return 0;
    }

    if (isset ($msg->left_chat_members)) {
      return count ($msg->left_chat_members);
    }

    return 1;
  }

  public static function getQtdNewUser ($jsonMessage = null)
  {
    $msg = self::getMessageInfo ($jsonMessage);

    if (!self::isNewUserMessage ($jsonMessage)) {
      return 0;
    }

    if (isset ($msg->new_chat_members)) {
      return count ($msg->new_chat_members);
    }

    return 1;
  }

  private static function getFromData ($fromObj)
  {
    return [
      "id" => $fromObj->id ?? '',
      "first_name" => $fromObj->first_name ?? '',
      "last_name" => $fromObj->last_name ?? '',
      "is_bot" => $fromObj->is_bot ?? '',
      "language_code" => $fromObj->language_code ?? ''
    ];
  }

  private static function getChatData ($chatObj)
  {
    return [
      "id" => $chatObj->id ?? '',
      "first_name" => $chatObj->first_name ?? '',
      "last_name" => $chatObj->last_name ?? '',
      "title" => $chatObj->title ?? '',
      "type" => $chatObj->type ?? '',
      "username" => $chatObj->username ?? '',
      "photo" => self::getChatPhotoData ($chatObj->photo ?? []),
      "description" => $chatObj->description ?? '',
      "invite_link" => $chatObj->invite_link ?? '',
      "pinned_message" => $chatObj->pinned_message ?? '',
      "sticker_set_name" => $chatObj->sticker_set_name ?? '',
      "can_set_sticker_set" => $chatObj->can_set_sticker_set ?? '',
      "all_members_are_administrators" => $chatObj->all_members_are_administrators ?? 0
    ];
  }

  private static function getContactData ($contactObj)
  {
    return [
      "is_telegram_user" => isset ($contactObj->user_id),
      "user_id" => $contactObj->user_id ?? '',
      "phone_number" => $contactObj->phone_number ?? '',
      "first_name" => $contactObj->first_name ?? '',
      "last_name" => $contactObj->last_name ?? '',
      "vcard" => $contactObj->vcard ?? ''
    ];
  }

  private static function getLocationData ($locationObj)
  {
    return [
      "latitude" => $locationObj->user_id ?? '',
      "longitude" => $locationObj->phone_number ?? ''
    ];
  }

  private static function getDocumentData ($documentObj)
  {
    return [
      "file_name" => $documentObj->file_name ?? '',
      "file_id" => $documentObj->file_id ?? '',
      "file_size" => $documentObj->file_size ?? '',
      "mime_type" => $documentObj->mime_type ?? '',
      "thumb" => self::getPhotoData ($documentObj->thumb ?? null)
    ];
  }

  private static function getPhotoData ($photoData)
  {
    return [
      "file_id" => $photoData->file_id ?? '',
      "file_size" => $photoData->file_size ?? '',
      "width" => $photoData->width ?? '',
      "height" => $photoData->height ?? ''
    ];
  }

  private static function getAudioData ($audioData)
  {
    return [
      "file_id" => $audioData->file_id   ?? '',
      "file_size" => $audioData->file_size ?? '',
      "duration" => $audioData->duration  ?? '',
      "mime_type" => $audioData->mime_type ?? '',
      "performer" => $audioData->performer ?? '',
      "title" => $audioData->title ?? '',
      "thumb"=> self::getPhotoData ($audioData->thumb ?? null)
    ];
  }

  private static function getVoiceData ($voiceData)
  {
    return [
      "file_id" => $voiceData->file_id ?? '',
      "file_size" => $voiceData->file_size ?? '',
      "duration" => $voiceData->duration ?? '',
      "mime_type" => $voiceData->mime_type ?? ''
    ];
  }

  private static function getVideoData ($videoData)
  {
    return [
      "file_id" => $videoData->file_id ?? '',
      "file_size" => $videoData->file_size ?? '',
      "duration" => $videoData->duration ?? '',
      "mime_type" => $videoData->mime_type ?? '',
      "width" => $videoData->width ?? '',
      "height" => $videoData->height ?? '',
      "thumb" => self::getPhotoData ($videoData->thumb ?? null)
    ];
  }

  private static function getAnimationData ($aninationData)
  {
    return [
      "file_id" => $aninationData->file_id ?? '',
      "file_size" => $aninationData->file_size ?? '',
      "file_name" => $aninationData->file_name ?? '',
      "duration" => $aninationData->duration ?? '',
      "mime_type" => $aninationData->mime_type ?? '',
      "width" => $aninationData->width ?? '',
      "height" => $aninationData->height ?? '',
      "thumb" => self::getPhotoData ($aninationData->thumb ?? null)
    ];
  }

  private static function getEntityData ($entityData)
  {
    return [
      "type" => $entityData->type ?? '',
      "offset" => $entityData->offset ?? '',
      "length" => $entityData->length ?? '',
      "url" => $entityData->url ?? '',
      "entity" => '',
      "param" => '',
      "user" => self::getFromData ($entityData->user ?? null)
    ];
  }

  private static function getChatPhotoData ($chatphotoData)
  {
    return [
      "small_file_id" => $chatphotoData->small_file_id ?? '',
      "big_file_id" => $chatphotoData->big_file_id ?? ''
    ];
  }
}


/* ------------------- Como usar ------------------- */

//Inicializa a classe
$mJson = new messageTelegram();

$path = 'F:\\TELEGRAM\\TOREAD\\';
$fls = scandir( $path );

//Elimina os dois primeiros termos com os pontos
$fls = array_slice( $fls, 2 - count( $fls ) );

$linerepeat = str_repeat( '-=', 20 ) . "-\n";

foreach( $fls as $arquivo ) {
  $contentFile = file_get_contents( $path . $arquivo );
	
  //Set a STRING com o JSON do update do telegram
  $mJson->setMessageJson( $contentFile );

  if (true) {
    echo 'Update ID      : ' . $mJson->getUpdateId() . "\n";
    echo 'Message ID     : ' . $mJson->getMessageId() . "\n";
    echo 'From           : ' . $mJson->getFromId() . ' - ' . $mJson->getFromFullName() . "\n";
    echo 'Chat           : ' . $mJson->getChatId() . ' - ' . $mJson->getChatFullName() . "\n";
    echo 'Date           : ' . $mJson->getDate() . "\n";
    echo 'Qtd. Left User : ' . $mJson->getQtdLeftUser() . "\n";
    echo 'Qtd. New User  : ' . $mJson->getQtdNewUser() . "\n";
    echo 'Text           : ' . $mJson->getText() . "\n";
    echo 'Bot Command    : ' . $mJson->getBotCommand() . "\n";
    echo 'Entities       : ';
    print_r( $mJson->getEntities() ) . "\n";
    echo $linerepeat;
  }
}
