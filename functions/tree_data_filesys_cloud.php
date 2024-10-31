<?php
if (!defined("ABSPATH")) {
    die('We\'re sorry, but you can not directly access this file.');
}
// Definindo limites de memória e tempo de execução
ini_set("memory_limit", "512M");
set_time_limit(60);
// Verifica se o arquivo autoload.php está no diretório especificado
$path = S3CLOUDPATH . "/vendor/autoload.php";
if (!file_exists($path)) {
    error_log("Autoload file not found.");
    die("Autoload file not found.");
}
// Definindo variáveis globais necessárias
global $s3cloud_region, $s3cloud_secret_key, $s3cloud_access_key, $s3cloud_bucket_name;
// Sanitiza o nome do bucket vindo do POST
if (isset($_POST["bucket_name"])) {
    $s3cloud_bucket_name = sanitize_text_field($_POST["bucket_name"]);
} else {
    error_log("Missing Post bucket_name");
    die("Missing Post bucket_name");
}
// Verifica se todas as variáveis globais estão definidas
if (!isset($s3cloud_region, $s3cloud_secret_key, $s3cloud_access_key, $s3cloud_bucket_name)) {
    //error_log($s3cloud_secret_key);
    error_log("Missing global configuration.");
    die("Missing global configuration.");
}
// Endpoint para o serviço S3
$endpoints = "https://" . $s3cloud_region . ".contabostorage.com";
// Carrega o arquivo de autoload para AWS SDK
require_once $path;
// 2024
use Aws\Exception\AwsException;
// Configuração para acesso ao S3
$config = [
    "s3-access" => [
        "key" => $s3cloud_access_key,
        "secret" => $s3cloud_secret_key,
        "bucket" => $s3cloud_bucket_name,
        "region" => $s3cloud_region,
        "version" => "latest",
        "endpoint" => $endpoints,
    ],
];
try {
    // Cria um cliente S3
    $s3 = new Aws\S3\S3Client([
        "credentials" => [
            "key" => $config["s3-access"]["key"],
            "secret" => $config["s3-access"]["secret"],
        ],
        "use_path_style_endpoint" => true,
        "force_path_style" => true,
        "endpoint" => $config["s3-access"]["endpoint"],
        "version" => "latest",
        "region" => $config["s3-access"]["region"],
    ]);
    // Lista os objetos no bucket especificado
    $objects = $s3->getIterator("ListObjects", [
        "Bucket" => $config["s3-access"]["bucket"],
    ]);
} catch (Aws\Exception\AwsException $e) {
    // Captura exceções da AWS SDK
    echo "Error Code: " . esc_attr($e->getAwsErrorCode()) . "\n";
    echo "Status Code: " . esc_attr($e->getStatusCode()) . "\n";
    echo "Message: " . esc_attr(explode(";", $e->getMessage())[1]) . "\n";
    die();
}


$elements = iterator_to_array($objects);
// Function to decode S3 object keys
function s3cloud_decodeS3Key($key) {
    return rawurldecode($key);
}

/*
function s3cloud_buildTree(array $elements) {
    $tree = [];
    foreach ($elements as $element) {
        // Obter o caminho completo do arquivo
        $filePath = decodeS3Key($element['Key']);
        // Verificar se o caminho contém diretórios (ignorar arquivos na raiz)
        if (strpos($filePath, '/') === false) {
            continue;
        }
        // elimina o nome do arq...
        $filePath = dirname($filePath);

        // Explodir o caminho em partes (diretórios e arquivos)
        $parts = explode('/', $filePath);
        // Construir a árvore baseada nos diretórios do caminho
        $currentLevel = &$tree;
        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }
            $directoryName = $part;
            $found = false;
            // Procurar pelo diretório atual dentro do nível atual da árvore
            foreach ($currentLevel as &$node) {
                if ($node['text'] === $directoryName) {
                    // Encontrou o diretório, mudar para o próximo nível
                    $currentLevel = &$node['nodes'];
                    $found = true;
                    break;
                }
            }
            // Se não encontrou o diretório, adicionar um novo nó
            if (!$found) {
                $newNode = [
                    'text' => $directoryName,
                    'nodes' => [],
                    'icon' => 'bi bi-folder'
                ];
                $currentLevel[] = $newNode;
                $currentLevel = &$newNode['nodes']; // Mudar para o próximo nível
            }
        }
        // Remover a referência
        unset($currentLevel);
    }
    return $tree;
}
*/

/*
function s3cloud_buildTree(array $elements) {
    $tree = [];
    foreach ($elements as $element) {
        // Obter o caminho completo do arquivo
        $filePath = decodeS3Key($element['Key']);
        // Verificar se o caminho contém diretórios (ignorar arquivos na raiz)
        if (strpos($filePath, '/') === false) {
            continue;
        }
        // elimina o nome do arq...
        $filePath = dirname($filePath);

        // Explodir o caminho em partes (diretórios e arquivos)
        $parts = explode('/', $filePath);
        // Construir a árvore baseada nos diretórios do caminho
        $currentLevel = &$tree;
        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }
            $directoryName = $part;
            $found = false;
            // Procurar pelo diretório atual dentro do nível atual da árvore
            foreach ($currentLevel as &$node) {
                if ($node['text'] === $directoryName) {
                    // Encontrou o diretório, mudar para o próximo nível
                    $currentLevel = &$node['nodes'];
                    $found = true;
                    break;
                }
            }
            // Se não encontrou o diretório, adicionar um novo nó
            if (!$found) {
                $newNode = [
                    'text' => $directoryName,
                    'nodes' => [],
                    'icon' => 'bi bi-folder'
                ];
                $currentLevel[] = $newNode;
                $currentLevel = &$newNode['nodes']; // Mudar para o próximo nível
            }
        }
        // Remover a referência
        unset($currentLevel);
    }
    $tree = '[{"text":"Root","icon":"","nodes":['. $tree . ']}]';
    return $tree;
}
*/

/*
$treeviewData = s3cloud_buildTree($elements );
unset($elements);
//error_log(var_export($treeviewData,true));
// Converte a estrutura da árvore para JSON
$tree_json = json_encode($treeviewData);
//// error_log($tree_json);
//error_log(__LINE__);
// Retorna o JSON para ser processado pelo JavaScript
die($tree_json);
*/

function s3cloud_buildTree(array $elements) {
    $tree = [];
    foreach ($elements as $element) {
        // Obter o caminho completo do arquivo
        $filePath = s3cloud_decodeS3Key($element['Key']);
        // Verificar se o caminho contém diretórios (ignorar arquivos na raiz)
        if (strpos($filePath, '/') === false) {
            continue;
        }
        // elimina o nome do arq...
        $filePath = dirname($filePath);

        // Explodir o caminho em partes (diretórios e arquivos)
        $parts = explode('/', $filePath);
        // Construir a árvore baseada nos diretórios do caminho
        $currentLevel = &$tree;
        foreach ($parts as $part) {
            if ($part === '' or $part === '.' or $part === '..'  ) {
                continue;
            }
            $directoryName = $part;
            $found = false;
            // Procurar pelo diretório atual dentro do nível atual da árvore
            foreach ($currentLevel as &$node) {
                if ($node['text'] === $directoryName) {
                    // Encontrou o diretório, mudar para o próximo nível
                    $currentLevel = &$node['nodes'];
                    $found = true;
                    break;
                }
            }
            // Se não encontrou o diretório, adicionar um novo nó
            if (!$found) {
                $newNode = [
                    'text' => $directoryName,
                    'nodes' => [],
                    'icon' => 'bi bi-folder'
                ];
                $currentLevel[] = $newNode;
                $currentLevel = &$newNode['nodes']; // Mudar para o próximo nível
            }
        }
        // Remover a referência
        unset($currentLevel);
    }
    return $tree;
}

$treeviewData = s3cloud_buildTree($elements);
unset($elements);

// Envolver os dados no formato especificado
$wrappedTreeData = [
    [
        "text" => "Root",
        "icon" => "",
        "nodes" => $treeviewData
    ]
];

// Converte a estrutura da árvore para JSON
$tree_json = json_encode($wrappedTreeData);

// Retorna o JSON para ser processado pelo JavaScript
die($tree_json);
