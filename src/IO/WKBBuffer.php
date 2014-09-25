<?php

namespace Brick\Geo\IO;

use Brick\Geo\Exception\GeometryException;

/**
 * Buffer class for reading binary data out of a WKB binary string.
 */
class WKBBuffer
{
    /**
     * @var string
     */
    private $wkb;

    /**
     * @var integer
     */
    private $length;

    /**
     * @var integer
     */
    private $position = 0;

    /**
     * @var integer
     */
    private $machineByteOrder;

    /**
     * @var boolean
     */
    private $invert = false;

    /**
     * Class constructor.
     *
     * @param string $wkb
     */
    public function __construct($wkb)
    {
        $this->wkb = $wkb;
        $this->length = strlen($wkb);
        $this->machineByteOrder = WKBTools::getMachineByteOrder();
    }

    /**
     * Reads $length bytes from the buffer.
     *
     * @param integer $length
     *
     * @return string
     *
     * @throws \Brick\Geo\Exception\GeometryException
     */
    private function read($length)
    {
        if ($this->position + $length > $this->length) {
            throw GeometryException::invalidWkb();
        }

        $data = substr($this->wkb, $this->position, $length);
        $this->position += $length;

        return $this->invert ? strrev($data) : $data;
    }

    /**
     * Reads one byte from the buffer.
     *
     * @return integer
     */
    private function readByte()
    {
        $data = unpack('cbyte', $this->read(1));

        return $data['byte'];
    }

    /**
     * Reads the machine byte order from the buffer and stores the result to act accordingly.
     *
     * @throws \Brick\Geo\Exception\GeometryException
     */
    public function readByteOrder()
    {
        $byteOrder = $this->readByte();

        if ($byteOrder != WKBTools::BIG_ENDIAN && $byteOrder != WKBTools::LITTLE_ENDIAN) {
            throw GeometryException::invalidWkb();
        }

        $this->invert = ($byteOrder != $this->machineByteOrder);
    }

    /**
     * Reads an unsigned integer from the buffer.
     *
     * @return integer
     */
    public function readUnsignedInteger()
    {
        $data = unpack('Luint', $this->read(4));

        return $data['uint'];
    }

    /**
     * Reads double-precision floating point numbers from the buffer.
     *
     * @param integer $n The number of doubles to read.
     *
     * @return float[] A 1-based array containing the numbers.
     */
    public function readDoubles($n)
    {
        return unpack('d' . $n, $this->read(8 * $n));
    }

    /**
     * Checks whether the pointer is at the end of the buffer.
     *
     * @return boolean
     */
    public function isEndOfStream()
    {
        return $this->position == $this->length;
    }
}
