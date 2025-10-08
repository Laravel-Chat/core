<?php

declare(strict_types=1);

namespace Examples;

/**
 * Exemplos práticos de uso do Laravel Chat Package
 * após refatoração SOLID
 */

use Akira\LaravelChat\Actions\CreateConversationAction;
use Akira\LaravelChat\Actions\SendMessageAction;
use Akira\LaravelChat\Config\ChatConfig;
use App\Models\User;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;

final class UsageExamples
{
    /**
     * Exemplo 1: Verificar se um usuário pode enviar mensagens para outro
     */
    public function checkIfCanSendMessage(): void
    {
        $sender = User::find(1);
        $recipient = User::find(2);

        // Usando a policy configurada
        if ($sender->canSendMessagesTo($recipient)) {
            echo "Sender pode enviar mensagens para recipient\n";
        } else {
            echo "Sender NÃO pode enviar mensagens para recipient\n";
        }

        // Verificar do outro lado
        if ($recipient->canReceiveMessagesFrom($sender)) {
            echo "Recipient aceita mensagens de sender\n";
        }
    }

    /**
     * Exemplo 2: Criar conversa usando Action
     */
    public function createConversationWithAction(): void
    {
        $creator = User::find(1);
        $participant = User::find(2);

        // Injetar action
        $action = app(CreateConversationAction::class);

        try {
            // Criar conversa direta
            $result = $action->handle(
                creator: $creator,
                type: 'direct',
                participantIds: [$participant->id],
                title: null
            );

            if ($result['existing']) {
                echo "Conversa já existe: {$result['id']}\n";
            } else {
                echo "Conversa criada: {$result['id']}\n";
            }
        } catch (Exception $e) {
            echo "Erro: {$e->getMessage()}\n";
            // Pode ser: "Este utilizador não aceita mensagens."
        }
    }

    /**
     * Exemplo 3: Enviar mensagem usando Action
     */
    public function sendMessageWithAction(): void
    {
        $user = User::find(1);
        $conversationId = 1;

        $action = app(SendMessageAction::class);

        try {
            $message = $action->handle(
                user: $user,
                conversationId: $conversationId,
                content: 'Olá! Como vai?',
                type: 'text',
                metadata: null
            );

            echo "Mensagem enviada: {$message->id}\n";
        } catch (Exception $e) {
            echo "Erro ao enviar: {$e->getMessage()}\n";
        }
    }

    /**
     * Exemplo 4: Criar conversa em grupo
     */
    public function createGroupConversation(): void
    {
        $creator = User::find(1);
        $participants = [2, 3, 4]; // IDs dos participantes

        $action = app(CreateConversationAction::class);

        $result = $action->handle(
            creator: $creator,
            type: 'group',
            participantIds: $participants,
            title: 'Grupo de Trabalho'
        );

        echo "Grupo criado: {$result['id']}\n";
    }

    /**
     * Exemplo 5: Acessar configurações
     */
    public function accessConfiguration(): void
    {
        $config = ChatConfig::getInstance();

        // Verificar se broadcasting está habilitado
        if ($config->isBroadcastingEnabled()) {
            echo "Broadcasting ativo\n";
            echo "Canal prefix: {$config->getBroadcastingChannelPrefix()}\n";
        }

        // Validar tipos de mensagem
        $types = ['text', 'image', 'video'];
        foreach ($types as $type) {
            $valid = $config->isValidMessageType($type);
            echo "$type: ".($valid ? 'válido' : 'inválido')."\n";
        }

        // Paginação
        echo "Conversas por página: {$config->getConversationsPerPage()}\n";
        echo "Mensagens por página: {$config->getMessagesPerPage()}\n";
    }

    /**
     * Exemplo 6: Verificar permissões antes de criar conversa
     */
    public function createConversationWithCheck(): void
    {
        $user1 = User::find(1);
        $user2 = User::find(2);

        // Verificar primeiro se pode criar conversa
        if (! $user1->canSendMessagesTo($user2)) {
            echo "Você não pode enviar mensagens para este usuário\n";

            return;
        }

        if (! $user2->canReceiveMessagesFrom($user1)) {
            echo "Este usuário não aceita suas mensagens\n";

            return;
        }

        // Criar conversa
        $action = app(CreateConversationAction::class);
        $result = $action->handle($user1, 'direct', [$user2->id]);

        echo "Conversa criada com sucesso!\n";
    }

    /**
     * Exemplo 7: Enviar mensagem com metadados
     */
    public function sendMessageWithMetadata(): void
    {
        $user = User::find(1);
        $action = app(SendMessageAction::class);

        // Mensagem com arquivo
        $message = $action->handle(
            user: $user,
            conversationId: 1,
            content: 'Confira este documento',
            type: 'file',
            metadata: [
                'file_url' => 'https://example.com/doc.pdf',
                'file_name' => 'documento.pdf',
                'file_size' => 1024000,
                'mime_type' => 'application/pdf',
            ]
        );

        echo "Arquivo enviado: {$message->metadata['file_name']}\n";
    }

    /**
     * Exemplo 8: Contar mensagens não lidas
     */
    public function countUnreadMessages(): void
    {
        $user = User::find(1);

        $unreadCount = $user->unreadMessagesCount();

        echo "Você tem $unreadCount mensagens não lidas\n";
    }

    /**
     * Exemplo 9: Listar conversas do usuário
     */
    public function listUserConversations(): void
    {
        $user = User::find(1);

        // Usando relationship do trait HasConversations
        $conversations = $user->conversations;

        foreach ($conversations as $conversation) {
            echo "Conversa #{$conversation->id}: {$conversation->title}\n";
            echo "Última mensagem: {$conversation->last_message_at}\n";
            echo "---\n";
        }
    }

    /**
     * Exemplo 10: Tratamento de erros
     */
    public function handleErrors(): void
    {
        $user = User::find(1);
        $action = app(SendMessageAction::class);

        try {
            // Tentar enviar para conversa que não existe
            $action->handle($user, 999999, 'Test');
        } catch (ModelNotFoundException $e) {
            echo "Conversa não encontrada ou você não é participante\n";
        } catch (Exception $e) {
            echo "Erro: {$e->getMessage()}\n";
        }
    }
}
