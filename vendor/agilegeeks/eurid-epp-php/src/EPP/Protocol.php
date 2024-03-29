<?php

namespace AgileGeeks\EPP;

class EPP_Protocol
{
    static function _fread_nb($socket, $length)
    {
        $result = '';

        // Loop reading and checking info to see if we hit timeout
        $info       = stream_get_meta_data($socket);
        $time_start = microtime(true);

        while (!$info['timed_out'] && !feof($socket)) {
            // Try read remaining data from socket
            $buffer = fread($socket, $length - strlen($result));

            // If the buffer actually contains something then add it to the result
            if ($buffer !== false) {
                $result .= $buffer;
                // If we hit the length we looking for, break
                if (strlen($result) == $length) {
                    break;
                }
            } else {
                // Sleep 0.25s
                usleep(250000);
            }

            // Update metadata
            $info     = stream_get_meta_data($socket);
            $time_end = microtime(true);
            if (($time_end - $time_start) > 10000000) {
                throw new \Exception('Timeout while reading from EPP Server');
            }
        }

        // Check for timeout
        if ($info['timed_out']) {
            throw new \Exception('Timeout while reading data from socket');
        }

        return $result;
    }


    static function _fwrite_nb($socket, $buffer, $length)
    {
        // Loop writing and checking info to see if we hit timeout
        $info       = stream_get_meta_data($socket);
        $time_start = microtime(true);

        $pos = 0;
        while (!$info['timed_out'] && !feof($socket)) {
            // Some servers don't like alot of data, so keep it small per chunk
            $wlen = $length - $pos;
            $wlen = $wlen > 1024 ? 1024 : $wlen;

            // Try write remaining data from socket
            $written = fwrite($socket, substr($buffer, $pos), $wlen);
            // If we read something, bump up the position
            if ($written && $written !== false) {
                $pos += $written;
                // If we hit the length we looking for, break
                if ($pos == $length) {
                    break;
                }
            } else {
                // Sleep 0.25s
                usleep(250000);
            }

            // Update metadata
            $info     = stream_get_meta_data($socket);
            $time_end = microtime(true);
            if (($time_end - $time_start) > 10000000) {
                throw new \Exception('Timeout while writing to EPP Server');
            }
        }
        // Check for timeout
        if ($info['timed_out']) {
            throw new \Exception('Timeout while writing data to socket');
        }

        return $pos;
    }

    /**
    * get an EPP frame from the remote peer
    * @param resource $socket a socket connected to the remote peer
    * @throws Exception on frame errors.
    * @return string the frame
    */
    static function getFrame($socket)
    {
        // Read header
        $hdr = EPP_Protocol::_fread_nb($socket, 4);

        // Unpack first 4 bytes which is our length
        $unpacked = unpack('N', $hdr);
        $length   = $unpacked[1];
        if ($length < 5) {
            throw new \Exception(sprintf('Got a bad frame header length of %d bytes from peer', $length));

        } else {
            $length -= 4; // discard the length of the header itself
            // Read frame
            return EPP_Protocol::_fread_nb($socket, $length);
        }
    }

    /**
    * send an EPP frame to the remote peer
    * @param resource $socket a socket connected to the remote peer
    * @param string $xml the XML to send
    * @throws Exception when it doesn't complete the write to the socket
    * @return the amount of bytes written to the frame
    */
    static function sendFrame($socket, $xml)
    {
        // Grab XML length & add on 4 bytes for the counter
        $length = strlen($xml) + 4;
        $res    = EPP_Protocol::_fwrite_nb($socket, pack('N', $length) . $xml, $length);
        // Check our write matches
        if ($length != $res) {
            throw new \Exception("Short write when sending XML");
        }

        return $res;
    }
}
