<?php
declare(strict_types=1);

namespace App\Models\Office;

use App\System\NexaModel;

class Delete extends NexaModel
{
    /**
     * Batch delete records with foreign-aware behavior similar to Insert/Update.
     *
     * Params items support:
     * - key: int (required)
     * - id: int (preferred for direct delete of that row)
     * - delete: array optional payload (ignored for now)
     *
     * Foreign items:
     * - key: int (target table key)
     * - failed: string[] where the first element is treated as FK field
     */
    public function buildDelete(array $params, array $foreign = null): array {
        $results = [];
        $forigin = $foreign;
        // Persist shared id from the first item
        $sharedId = null;

        foreach ($params as $index => $item) {
            try {
                $tableKey = (int)($item['key'] ?? 0);
                $table = $this->tablesIndex($tableKey) ?? null;
                if (!$table) {
                    throw new \InvalidArgumentException('Invalid table key.');
                }

                $id = isset($item['id']) ? (int)$item['id'] : null;

                if ($index === 0) {
                    if ($id === null) {
                        throw new \InvalidArgumentException('Missing id for primary delete item');
                    }
                    $sharedId = $id;
                    $this->Storage($table)
                        ->where('id', $sharedId)
                        ->delete();
                } else {
                    // Subsequent deletions
                    if (!empty($forigin) && is_array($forigin)) {
                        $handled = false;
                        foreach ($forigin as $rule) {
                            if ($tableKey === (int)($rule['key'] ?? -1)) {
                                $targetTable = $this->tablesIndex((int)$rule['key']);
                                if ($targetTable) {
                                    // Direct delete by id if provided
                                    if ($id !== null) {
                                        $this->Storage($targetTable)
                                            ->where('id', $id)
                                            ->delete();
                                    } else if (!empty($rule['failed']) && is_array($rule['failed'])) {
                                        $fkField = $rule['failed'][0];
                                        $this->Storage($targetTable)
                                            ->where($fkField, $sharedId)
                                            ->delete();
                                    }
                                    $handled = true;
                                }
                            }
                        }
                        if (!$handled && $id !== null) {
                            // Fallback: no foreign rule matched, but id provided
                            $this->Storage($table)
                                ->where('id', $id)
                                ->delete();
                        }
                    } else if ($id !== null) {
                        // No foreign rules: delete by id if provided
                        $this->Storage($table)
                            ->where('id', $id)
                            ->delete();
                    }
                }

                $results[] = [
                    'success' => true,
                    'message' => 'Deleted successfully'
                ];
            } catch (\Throwable $e) {
                $results[] = [
                    'key' => $item['key'] ?? null,
                    'success' => false,
                    'message' => 'Delete operation failed: ' . $e->getMessage()
                ];
            }
        }

        return [
            'success' => true,
            'results' => $results,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
}


