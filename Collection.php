<?php

class Collection implements ArrayAccess, IteratorAggregate {

  private $items;
  
  private $size;

  /**
   * @return int <p>number of rows in the collection</p>
   */
  public function num_rows (): int {
    return $this->size;
  }
  
  /**
   * modify the collection to become a copy of the collection given as param
   * @param Collection $collection <p>Collection to copy</p>
   * @return self
   */
  public function copy (Collection $collection): self {
    $this->clear();
    foreach ($collection as $row) {
      $this->push($row);
    }
    return $this;
  }
  
  /**
   * erraze the entire collection
   * @return self
   */
  public function clear (): self {
    $this->items = [];
    $this->size = 0;
    return $this;
  }
  
  /**
   * <p>add element at the end of collection</p>
   * @param mixed $value <p>element to add</p>
   * @return self
   */
  public function push ($value): self {
    if ($value instanceof Collection) {
      foreach($value as $row) {
        $this->push($row);
      }
    }
    else {
      $this->items[] = $value;
      $this->size ++;
    }
    return $this;
  }
  
  /**
   * <p>add element at the begining of collection</p>
   * @param mixed $value <p>element to add</p>
   * @return self
   */
  public function unshift ($value): self {
    $result = new Collection ([]);
    if ($value instanceof Collection) {
      $result->copy($value);
    }
    else {
      result->push($value);
    }
    foreach ($this as $row) {
      $result->push($row);
    }
    return $this->copy($result);
  }
  
  /**
   * <p>remove last element from the collection</p>
   * @return self
   */
  public function pop (): self {
    $this->remove($this->num_rows() - 1);
    $this->size --;
    return $this;
  }
  
  /**
   * <p>remove first element from the collection</p>
   * @return self
   */
  public function shift (): self {
    return $this->copy($this->slice(1));
  }

  /**
   * @param Collection $collection <p>Collection object to merge to this</p>
   * @param string $id <p>columns name to check for update instead of insert</p>
   * @return self
   */
  public function merge (Collection $collection, string $id = 'id', bool $recursive = false, bool $keep = false): self {
    foreach ($collection as $row) {
      $exists = $i = 0;
      while (!$exists && $i < $this->num_rows()) {
        if ($this->items[$i][$id] == $row[$id]) {
          if ($recursive) {
            foreach ($this->items[$i] as $key => $value) {
              if ($value instanceof Collection && $row[$key] instanceof Collection) {
                $row[$key]->merge($value);
                $this->items[$i][$key] = $row[$key];
              }
            }
          }
          if (!$keep) {
            $this->items[$i] = $row;
          }
          $exists = 1;
        }
        $i++;
      }
      if (!$exists) {
        $this->push($row);
      }
    }
    return $this;
  }

  /**
   * @param string $key <p>sort by column</p>
   * @param string $order <p>order to sort (asc or desc)</p>
   * @return self
   */
  public function sort (string $key = "id", string $order = "ASC"): self {
    [$asc, $desc] = [in_array($order, ["ASC", "asc", "<"]), in_array($order, ["DESC", "desc", ">"])];
    if ($this->num_rows() > 1 && ($asc || $desc)) {
      $half = round($this->num_rows() / 2);
      $left = $this->slice(0, $half)->sort($key, $order);
      $right = $this->slice($half)->sort($key, $order);

      $this->clear();
      $i = $j = 0;
      while ($i < $left->num_rows() && $j < $right->num_rows()) {
        if (($desc && $left[$i][$key]>$right[$j][$key]) || ($asc && $left[$i][$key]<$right[$j][$key])) {
          $this->push($left[$i]);
          $i++;
        }
        else {
          $this->push($right[$j]);
          $j++;
        }
      }
      while ($i<$left->num_rows() || $j<$right->num_rows()) {
        $this->push($i<$left->num_rows() ? $left[$i] : $right[$j]);
        $i++; $j++;
      }
    }
    return $this;
  }
  
  /**
   * use regex to find matches in values of a column
   * @param string $column <p>column to look at</p>
   * @param mixed $value <p>int or string, can use regex syntax</p>
   * @return Collection <p>new Collection containing the rows that matched</p>
   */
  public function search (string $column, $value): Collection {
    return $this->filter(function ($row) use ($column, $value) {
      preg_match("/{$value}/", $row[$column], $match);
      return $match;
    });
  }
  
  /**
   * @exemple <p>
   * $column = 'name';
   * $value = "Hello World!";
   * $filtered = $collection->filter(function ($row) use ($column, $value) { return $row[$column] == $value; });
   * </p>
   * @param callable $callback <p>test function that return a boolean, take each rows as param</p>
   * @return Collection <p>new Collection containing the rows that passed the test</p>
   */
  public function filter (callable $callback): Collection {
    $result = new Collection ([]);
    foreach($this as $row) {
      if ($callback($row)) {
        $result->push($row);
      }
    }
    return $result;
  }

  /**
   * cut a part of the collection starting from $index and taking $length elements.
   * if $length is not set (or set to NULL), will run until the end of the
   * collection.
   * @param int $index <p>the sequence start at that offset in the collection</p>
   * @param int $length <p>number of element to retrieve from $index position</p>
   * @return Collection <p>new Collection containing the rows from $index to $index+$length</p>
   */
  public function slice (int $index, ?int $length = NULL): Collection {
    $result = new Collection ([]);
    $i = $index;
    $length = ($length == NULL) ? $this->num_rows()-$i : $length;
    while ($i<$this->num_rows() && $i<$index+$length) {
      $result->push($this->items[$i]);
      $i++;
    }
    return $result;
  }
  
  /**
   * @param strin $key <p>column to look at</p>
   * @return mixed <p>greatest value of the column</p>
   */
  public function maximum (string $key): mixed {
    $max = 0;
    foreach($this as $row) {
      if ($row[$key] > $max) {
        $max = $row[$key];
      }
    }
    return $max;
  }
  
  /**
   * @param strin $key <p>column to look at</p>
   * @return mixed <p>smallest value of the column</p>
   */
  public function minimum (string $key): mixed {
    $min = $this[0][$key];
    foreach($this as $row) {
      if ($row[$key] < $min) {
        $min = $row[$key];
      }
    }
    return $min;
  }
  
  /**
   * @param strin $key <p>column to look at</p>
   * @return float <p>average value of the column</p>
   */
  public function average (string $key): float {
    $avg = 0;
    foreach($this as $row) {
      $avg += (float) $row[$key];
    }
    return $avg / $this->num_rows();
  }

  public function __construct (array $items) {
    $count = 0;
    $size = 0;
    foreach ($items as $key => $value) {
      if ($key == $count) {
        $count ++;
      }
      $size ++;
    }
    $this->items = $size == $count ? $items : [$items];
    $this->size = $size == $count ? $size : 1;
  }

  /**
   * @param string $key <p>key exists to check</p>
   * @return boolean <p>true on success or false on failure.</p>
   */
  public function exists ($key): bool {
    return array_key_exists($key, $this->items);
  }

  /**
   * @param string $key <p>key to get the value from</p>
   * @return mixed <p>value assigned to the key</p>
   */
  public function get ($key) {
    return $this->exists($key) ? $this->items[$key] : false;
  }

  /**
   * @param string $key <p>key to assign a value to</p>
   * @param string $value <p>value to assign</p>
   * @return void
   */
  public function set ($key, $value) {
    $this->items[$key] = $value;
  }

  /**
   * @param string $key <p>key to remove</p>
   * @return void
   */
  public function remove ($key) {
    if ($this->exists($key)) {
      unset($this->items[$key]);
    }
  }

  /**
   * @link http://php.net/manual/en/arrayaccess.offsetexists.php
   * @param mixed $offset <p>An offset to check for.</p>
   * @return boolean <p>true on success or false on failure.</p>
   */
  public function offsetExists($offset): bool {
    return $this->exists($offset);
  }

  /**
   * @link http://php.net/manual/en/arrayaccess.offsetget.php
   * @param mixed $offset <p>The offset to retrieve.</p>
   * @return mixed <p>can return all value type</p>
   */
  public function offsetGet($offset) {
    return $this->get($offset);
  }

  /**
   * @link http://php.net/manual/en/arrayaccess.offsetset.php
   * @param mixed $offset <p>The offset to assign the value to.</p>
   * @param mixed $value <p>The value to set</p>
   * @return void
   */
  public function offsetSet($offset, $value) {
    return $this->set($offset, $value);
  }

  /**
   * @link http://php.net/manual/en/arrayaccess.offsetunset.php
   * @param mixed $offset <p>The offset to unset.</p>
   * @return void
   */
  public function offsetUnset($offset) {
    $this->remove($offset);
  }

  /**
   * @link http://php.net/manual/en/iteratoraggregate.getiterator.php
   * @return Traversable <p>instance of an object implementing <b>Iterator</b></p>
   */
  public function getIterator() {
    return new ArrayIterator($this->items);
  }

}

?>
