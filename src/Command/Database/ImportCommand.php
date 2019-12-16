<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Chash\Command\Database;

use Symfony\Component\Console;
use Symfony\Component\Console\Input\InputArgument;

/**
 * Task for executing arbitrary SQL that can come from a file or directly from
 * the command line.
 *
 * @see    www.doctrine-project.org
 * @since   2.0
 *
 * @author  Benjamin Eberlei <kontakt@beberlei.de>
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
class ImportCommand extends Console\Command\Command
{
    /**
     * @see Console\Command\Command
     */
    protected function configure(): void
    {
        $this
            ->setName('dbal:import')
            ->setDescription('Import SQL file(s) directly to Database.')
            ->setDefinition([
                    new InputArgument(
                        'file',
                        InputArgument::REQUIRED | InputArgument::IS_ARRAY,
                        'File path(s) of SQL to be executed.'
                    ),
                ])
            ->setHelp(
                <<<EOT
Import SQL file(s) directly to Database.
EOT
            );
    }

    /**
     * @see Console\Command\Command
     */
    protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output)
    {
        $conn = $this->getHelper('db')->getConnection();

        if (null !== ($fileNames = $input->getArgument('file'))) {
            foreach ((array) $fileNames as $fileName) {
                if (!file_exists($fileName)) {
                    throw new \InvalidArgumentException(sprintf("SQL file '<info>%s</info>' does not exist.", $fileName));
                } elseif (!is_readable($fileName)) {
                    throw new \InvalidArgumentException(sprintf("SQL file '<info>%s</info>' does not have read permissions.", $fileName));
                }

                $output->write(sprintf("Processing file '<info>%s</info>'... ", $fileName));
                $sql = file_get_contents($fileName);

                if ($conn instanceof \Doctrine\DBAL\Driver\PDOConnection) {
                    // PDO Drivers
                    try {
                        $lines = 0;

                        $stmt = $conn->prepare($sql);
                        $stmt->execute();

                        do {
                            // Required due to "MySQL has gone away!" issue
                            $stmt->fetch();
                            $stmt->closeCursor();

                            ++$lines;
                        } while ($stmt->nextRowset());

                        $output->write(sprintf('%d statements executed!', $lines).PHP_EOL);
                    } catch (\PDOException $e) {
                        $output->write('error!'.PHP_EOL);

                        throw new \RuntimeException($e->getMessage(), $e->getCode(), $e);
                    }
                } else {
                    // Non-PDO Drivers (ie. OCI8 driver)
                    $stmt = $conn->prepare($sql);
                    $rs = $stmt->execute();

                    if ($rs) {
                        $output->writeln('OK!'.PHP_EOL);
                    } else {
                        $error = $stmt->errorInfo();

                        $output->write('error!'.PHP_EOL);

                        throw new \RuntimeException($error[2], $error[0]);
                    }

                    $stmt->closeCursor();
                }
            }
        }
    }
}
